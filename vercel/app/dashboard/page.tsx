export default function DashboardPage() {
  return (
    <div className="space-y-6">
      <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <h3 className="text-2xl font-bold mb-2">Bienvenido a EXA Relavera</h3>
        <p className="text-gray-600">
          Este es el nuevo panel de gestión construido con Next.js y Tailwind CSS, operando
          bajo una arquitectura desacoplada conectada a tu API en PHP.
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex flex-col items-center text-center">
          <div className="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mb-4 text-xl font-bold">
            M
          </div>
          <h4 className="font-semibold text-lg">Manifiestos</h4>
          <p className="text-sm text-gray-500 mt-2">
            Registra y haz seguimiento al movimiento de vehículos desde origen a la relavera.
          </p>
        </div>

        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex flex-col items-center text-center">
          <div className="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center mb-4 text-xl font-bold">
            T
          </div>
          <h4 className="font-semibold text-lg">Control Técnico</h4>
          <p className="text-sm text-gray-500 mt-2">
            Gestiona el tratamiento del material y el estado de aceptación en celdas.
          </p>
        </div>

        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100 flex flex-col items-center text-center">
          <div className="w-12 h-12 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mb-4 text-xl font-bold">
            A
          </div>
          <h4 className="font-semibold text-lg">Actores</h4>
          <p className="text-sm text-gray-500 mt-2">
            Administración de clientes, transportistas, vehículos y choferes con control de sanciones.
          </p>
        </div>
      </div>
    </div>
  );
}
