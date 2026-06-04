import { NextResponse } from "next/server";
import { exec } from "child_process";
import util from "util";
import path from "path";

const execPromise = util.promisify(exec);

export async function POST() {
  try {
    const projectDir = path.resolve(process.cwd());
    try {
      await execPromise("npx playwright test", { cwd: projectDir });
      return NextResponse.json({
        success: true,
        message: "¡Diagnóstico completado!",
        reportUrl: "/report/index.html",
      });
    } catch (execError: any) {
      return NextResponse.json({
        success: false,
        error: "El monitor ha detectado anomalías.",
        reportUrl: "/report/index.html",
      });
    }
  } catch (error: any) {
    return NextResponse.json(
      {
        success: false,
        error: error.message,
      },
      { status: 500 },
    );
  }
}
