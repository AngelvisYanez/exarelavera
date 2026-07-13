import { NextResponse } from "next/server";
import fs from "fs";
import path from "path";
import { checkRateLimit, getClientIp } from "@/lib/rate-limit";

const DEFAULT_MODEL = "gemini-2.5-flash";
const GEMINI_API_BASE_URL =
  "https://generativelanguage.googleapis.com/v1beta/models";
const HISTORY_FILE = path.join(process.cwd(), "data", "fix-history.json");

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

export async function GET() {
  if (fs.existsSync(HISTORY_FILE)) {
    return NextResponse.json(
      JSON.parse(fs.readFileSync(HISTORY_FILE, "utf-8")),
    );
  }
  return NextResponse.json([]);
}

function extractFilePath(culprit: string): string {
  return culprit.split(" in ")[0].trim();
}

function findFileByName(
  dir: string,
  targetFileName: string,
): string | null {
  if (!fs.existsSync(dir)) return null;
  const targetLower = targetFileName.toLowerCase();

  function walk(currentDir: string): string | null {
    let entries: fs.Dirent[];
    try {
      entries = fs.readdirSync(currentDir, { withFileTypes: true });
    } catch {
      return null;
    }
    for (const entry of entries) {
      const fullPath = path.join(currentDir, entry.name);
      if (entry.isDirectory()) {
        if (entry.name === "node_modules" || entry.name === ".next") continue;
        const found = walk(fullPath);
        if (found) return found;
      } else if (entry.isFile() && entry.name.toLowerCase() === targetLower) {
        return fullPath;
      }
    }
    return null;
  }

  return walk(dir);
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

  const fileName = path.basename(relativePath);
  const searchDirs = [cwd, path.resolve(cwd, "..")];

  for (const searchDir of searchDirs) {
    const found = findFileByName(searchDir, fileName);
    if (found) {
      return {
        absolutePath: found,
        content: fs.readFileSync(found, "utf-8"),
      };
    }
  }

  return null;
}

export async function POST(request: Request) {
  const ip = getClientIp(request);
  const { allowed, remaining } = checkRateLimit(ip);

  if (!allowed) {
    return NextResponse.json(
      { success: false, error: "Demasiadas peticiones. Intenta de nuevo en un minuto." },
      { status: 429, headers: { "Retry-After": "60", "X-RateLimit-Remaining": "0" } },
    );
  }

  try {
    const body = await request.json();
    const { action, title, culprit, stackTrace, suggestedCode } = body;

    const filePath = extractFilePath(culprit || "");
    const fileData = findFileInWorkspace(filePath);

    if (action === "apply") {
      if (!filePath) {
        return NextResponse.json(
          { success: false, error: "No se pudo identificar el archivo a corregir." },
          { status: 400 },
        );
      }
      if (!suggestedCode) {
        return NextResponse.json(
          { success: false, error: "No se proporcionó el código corregido." },
          { status: 400 },
        );
      }
      if (!fileData) {
        return NextResponse.json(
          { success: false, error: `No se encontró el archivo ${filePath} en el proyecto para aplicar los cambios.` },
          { status: 404 },
        );
      }

      fs.writeFileSync(fileData.absolutePath, suggestedCode, "utf-8");
      saveFixToHistory(filePath, "");

      return NextResponse.json({
        success: true,
        message: `¡Parche aplicado con éxito! El archivo ${filePath} ha sido actualizado correctamente.`,
      });
    }

    const apiKey = process.env.GEMINI_API_KEY;
    const modelName = process.env.GEMINI_MODEL || DEFAULT_MODEL;

    if (!fileData) {
      return NextResponse.json(
        { success: false, error: `No se encontró el archivo de origen: "${filePath}". Por favor verifica que la ruta exista en el repositorio.` },
        { status: 404 },
      );
    }

    if (!apiKey) {
      return NextResponse.json(
        { success: false, error: `No se configuró la API Key de Gemini. Configure GEMINI_API_KEY en .env para habilitar el agente IA.` },
        { status: 503 },
      );
    }

    const systemPrompt = `
Eres un agente de Inteligencia Artificial experto en depuración de código de software para sistemas PHP y React/Next.js con TypeScript. Operando bajo el motor avanzado de Gemini 3.0.
Tu tarea es analizar el error proporcionado, leer el código del archivo fuente adjunto y proponer la corrección exacta del bug de manera quirúrgica y segura.

DATOS DEL ERROR:
- Título del Error: ${title}
- Archivo e Interfaz afectada (Culpable): ${culprit}
- Traza de pila (Stack Trace):
${Array.isArray(stackTrace) ? stackTrace.join("\n") : stackTrace}

CÓDIGO FUENTE ACTUAL DEL ARCHIVO EFECTUADO (${filePath}):
\`\`\`
${fileData.content}
\`\`\`

REQUERIMIENTOS:
Debes responder ÚNICAMENTE con un objeto JSON válido (sin marcas de markdown de código como \`\`\`json). El JSON debe tener exactamente las siguientes propiedades:
1. "explanation": Explicación corta y técnica de por qué ocurre el error.
2. "suggestedFix": Resumen de qué líneas cambiar y por qué.
3. "fullCorrectedCode": El código fuente COMPLETO del archivo con la corrección aplicada. Debe compilar perfectamente y no omitir ninguna parte del código original.
4. "diff": Un diff visual en formato de texto indicando los cambios (- old, + new).

Responde solo el objeto JSON, asegúrate de escapar correctamente los caracteres especiales y saltos de línea para que sea un JSON parseable.
`;

    const requestUrl = `${GEMINI_API_BASE_URL}/${modelName}:generateContent?key=${apiKey}`;
    const response = await fetch(requestUrl, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        contents: [{ parts: [{ text: systemPrompt }] }],
      }),
    });

    if (!response.ok) {
      const errData = await response.json();
      return NextResponse.json(
        { success: false, error: `Error al conectar con Gemini: ${errData?.error?.message || response.statusText}` },
        { status: response.status },
      );
    }

    const geminiData = await response.json();
    let aiText = geminiData?.candidates?.[0]?.content?.parts?.[0]?.text || "";
    aiText = aiText.replace(/^```json\s*/i, "").replace(/```\s*$/, "").trim();

    try {
      const parsedOutput = JSON.parse(aiText);
      return NextResponse.json({
        success: true,
        explanation: parsedOutput.explanation,
        suggestedFix: parsedOutput.suggestedFix,
        fullCorrectedCode: parsedOutput.fullCorrectedCode,
        diff: parsedOutput.diff,
      });
    } catch {
      return NextResponse.json(
        { success: false, error: "El agente de IA devolvió un formato no estructurado.", rawText: aiText },
        { status: 422 },
      );
    }
  } catch (error: unknown) {
    const message = error instanceof Error ? error.message : String(error);
    return NextResponse.json(
      { success: false, error: message || "Error del servidor al procesar el AI Auto-Fix." },
      { status: 500 },
    );
  }
}
