import { NextResponse } from "next/server";
import prisma from "@/lib/prisma";
import crypto from "crypto";

export async function POST(request: Request) {
  try {
    const { username, password } = await request.json();

    // 1. Buscamos al usuario en la tabla 'usuarios'
    const user = await prisma.usuario.findUnique({
      where: { usu_ced: username },
    });

    if (!user || user.usu_est !== "A") {
      return NextResponse.json(
        { success: false, error: "Usuario no encontrado o inactivo" },
        { status: 401 },
      );
    }

    // 2. Verificamos la contraseña (la original usa MD5)
    const passwordMd5 = crypto.createHash("md5").update(password).digest("hex");

    if (user.usu_pal !== passwordMd5) {
      return NextResponse.json(
        { success: false, error: "Credenciales inválidas" },
        { status: 401 },
      );
    }

    // 3. Verificamos acceso a empresas (consulta lógica de exa_master)
    // Buscamos si el usuario tiene algún acceso activo en 'access'
    const acceso = await prisma.acceso.findFirst({
      where: {
        acc_usr: username,
        acc_est: "A",
      },
    });

    if (!acceso) {
      return NextResponse.json(
        { success: false, error: "No tiene acceso a ninguna empresa" },
        { status: 403 },
      );
    }

    // Si todo es correcto, generamos el token
    return NextResponse.json({
      success: true,
      token: "superadmin_session_token_2026_success",
      empresaId: acceso.dat_cod,
    });
  } catch (error) {
    console.error("Error en autenticación:", error);
    return NextResponse.json(
      { success: false, error: "Error interno del servidor" },
      { status: 500 },
    );
  }
}
