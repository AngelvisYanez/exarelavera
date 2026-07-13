import { NextResponse } from 'next/server';
import crypto from 'crypto';
import { checkRateLimit, getClientIp } from '@/lib/rate-limit';

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
    const { username, password } = await request.json();

    const expectedUser = process.env.SUPERADMIN_USER;
    const expectedPass = process.env.SUPERADMIN_PASS;

    if (!expectedUser || !expectedPass) {
      return NextResponse.json({
        success: false,
        error: 'SUPERADMIN_USER y SUPERADMIN_PASS no están configurados en .env'
      }, { status: 500 });
    }

    if (username !== expectedUser || password !== expectedPass) {
      return NextResponse.json({
        success: false,
        error: 'Credenciales de Superadmin inválidas'
      }, { status: 401 });
    }

    const token = crypto.randomBytes(32).toString('hex');

    return NextResponse.json({
      success: true,
      token
    }, {
      headers: { 'X-RateLimit-Remaining': String(remaining) },
    });
  } catch {
    return NextResponse.json({
      success: false,
      error: 'Error en la verificación'
    }, { status: 500 });
  }
}
