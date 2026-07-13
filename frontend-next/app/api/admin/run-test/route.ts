import { NextResponse } from 'next/server';
import { exec } from 'child_process';
import util from 'util';
import path from 'path';
import { checkRateLimit, getClientIp } from '@/lib/rate-limit';

const execPromise = util.promisify(exec);

export async function POST(request: Request) {
  const ip = getClientIp(request);
  const { allowed, remaining } = checkRateLimit(ip);

  if (!allowed) {
    return NextResponse.json({
      success: false,
      error: 'Demasiadas peticiones. Intenta de nuevo en un minuto.'
    }, {
      status: 429,
      headers: { 'Retry-After': '60', 'X-RateLimit-Remaining': '0' },
    });
  }

  try {
    const projectDir = path.resolve(process.cwd());

    try {
      await execPromise('npx playwright test', { cwd: projectDir });

      return NextResponse.json({
        success: true,
        message: '¡Diagnóstico completado! Todos los flujos se ejecutaron correctamente.',
        reportUrl: '/report/index.html'
      }, {
        headers: { 'X-RateLimit-Remaining': String(remaining) },
      });
    } catch (execError: unknown) {
      const message = execError instanceof Error ? execError.message : String(execError);

      return NextResponse.json({
        success: false,
        error: message || 'El monitor sintético ha detectado anomalías o fallos en el sistema.',
        reportUrl: '/report/index.html'
      }, {
        headers: { 'X-RateLimit-Remaining': String(remaining) },
      });
    }
  } catch (error: unknown) {
    const message = error instanceof Error ? error.message : String(error);
    return NextResponse.json({
      success: false,
      error: message || 'Error del servidor al lanzar las pruebas automáticas.'
    }, { status: 500 });
  }
}
