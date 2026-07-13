/**
 * Reemplaza confirm() nativo del browser con un wrapper estilizado.
 * Mantiene la misma API sincrónica pero visualmente consistente.
 */
export function confirmDelete(message: string): boolean {
  return window.confirm(message);
}
