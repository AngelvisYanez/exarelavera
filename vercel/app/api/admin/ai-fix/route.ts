import { NextResponse } from "next/server";

const DEFAULT_MODEL = "gemini-2.5-flash";
const GEMINI_API_BASE_URL =
  "https://generativelanguage.googleapis.com/v1beta/models";

export async function POST(request: Request) {
  try {
    const body = await request.json();
    const { title, culprit, stackTrace } = body;

    const apiKey = process.env.GEMINI_API_KEY;
    const modelName = process.env.GEMINI_MODEL || DEFAULT_MODEL;

    if (!title) {
      return NextResponse.json(
        { success: false, error: "Falta el título de la incidencia" },
        { status: 400 },
      );
    }

    if (!apiKey) {
      return NextResponse.json({
        success: true,
        isSimulated: true,
        explanation:
          "No hay GEMINI_API_KEY configurada. Sugerencia: revisa la documentación del error y aplica el fix manualmente.",
        suggestedFix: "Revisar la configuración del servidor y logs.",
        fullCorrectedCode: "",
        diff: "",
        warning: `Configura GEMINI_API_KEY en .env para usar ${modelName}`,
      });
    }

    const systemPrompt = `Eres un experto debuggeando código. Dado un error, responde SOLO JSON con: explanation (explicación), suggestedFix (fix sugerido), fullCorrectedCode (código corregido si aplica), diff (diff del cambio). Error: ${title}${culprit ? ` en ${culprit}` : ""}${stackTrace?.length ? `\nStack: ${stackTrace.join("\\n")}` : ""}`;
    const requestUrl = `${GEMINI_API_BASE_URL}/${modelName}:generateContent?key=${apiKey}`;
    const response = await fetch(requestUrl, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ contents: [{ parts: [{ text: systemPrompt }] }] }),
    });

    const data = await response.json();
    const aiText = data?.candidates?.[0]?.content?.parts?.[0]?.text
      .replace(/^```json\s*/i, "")
      .replace(/```\s*$/, "")
      .trim();

    return NextResponse.json({ success: true, ...JSON.parse(aiText) });
  } catch (error: any) {
    return NextResponse.json(
      { success: false, error: error.message },
      { status: 500 },
    );
  }
}
