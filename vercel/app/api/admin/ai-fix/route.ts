import { NextResponse } from "next/server";
import fs from "fs";
import path from "path";

// Configuración del modelo de IA: Usando gemini-2.5-flash para el máximo rendimiento actual
// Configuración del modelo de IA: Usando gemini-2.5-flash para el máximo rendimiento actual
const DEFAULT_MODEL = "gemini-2.5-flash";
const GEMINI_API_BASE_URL =
  "https://generativelanguage.googleapis.com/v1beta/models";
const HISTORY_FILE = path.join(process.cwd(), "data", "fix-history.json");

// Helper para guardar el historial de cambios
function saveFixToHistory(filePath: string, diff: string) {
  let history = [];
  if (fs.existsSync(HISTORY_FILE)) {
    const data = fs.readFileSync(HISTORY_FILE, "utf-8");
    history = JSON.parse(data);
  }
  history.unshift({
    timestamp: new Date().toISOString(),
    file: filePath,
    diff: diff,
  });
  fs.writeFileSync(HISTORY_FILE, JSON.stringify(history.slice(0, 50), null, 2));
}

// Handler para listar historial
export async function GET() {
  if (fs.existsSync(HISTORY_FILE)) {
    return NextResponse.json(
      JSON.parse(fs.readFileSync(HISTORY_FILE, "utf-8")),
    );
  }
  return NextResponse.json([]);
}

function extractFilePath(culprit: string): string {
  const clean = culprit.split(" in ")[0].trim();
  return clean;
}

function findFileInWorkspace(
  relativePath: string,
): { absolutePath: string; content: string } | null {
  const cwd = process.cwd();
  const frontendPath = path.resolve(cwd, relativePath);
  const backendPath = path.resolve(cwd, "..", relativePath);

  if (fs.existsSync(frontendPath)) {
    return {
      absolutePath: frontendPath,
      content: fs.readFileSync(frontendPath, "utf-8"),
    };
  }
  if (fs.existsSync(backendPath)) {
    return {
      absolutePath: backendPath,
      content: fs.readFileSync(backendPath, "utf-8"),
    };
  }
  return null;
}

export async function POST(request: Request) {
  try {
    const body = await request.json();
    const { action, title, culprit, stackTrace, suggestedCode } = body;
    const filePath = extractFilePath(culprit || "");
    const fileData = findFileInWorkspace(filePath);

    if (action === "apply") {
      if (!filePath || !suggestedCode || !fileData)
        return NextResponse.json(
          { success: false, error: "Invalid input" },
          { status: 400 },
        );
      fs.writeFileSync(fileData.absolutePath, suggestedCode, "utf-8");
      return NextResponse.json({ success: true, message: "Parche aplicado." });
    }

    const apiKey = process.env.GEMINI_API_KEY;
    const modelName = process.env.GEMINI_MODEL || DEFAULT_MODEL;

    if (!fileData)
      return NextResponse.json(
        { success: false, error: "File not found" },
        { status: 404 },
      );

    if (!apiKey) {
      return NextResponse.json({
        success: true,
        isSimulated: true,
        explanation: "Simulated fix: Path traversal check added.",
        suggestedFix: "Add validation.",
        fullCorrectedCode: fileData.content,
        diff: "+ fix",
        warning: `Configure GEMINI_API_KEY for ${modelName}`,
      });
    }

    const systemPrompt = `Analyze and fix: ${title} in ${culprit}. Code: ${fileData.content}. Respond JSON with explanation, suggestedFix, fullCorrectedCode, diff.`;
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
