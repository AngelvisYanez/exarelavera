"use client";

import { ColumnDef } from "@tanstack/react-table";
import { ComprobanteRow, RetencionRow } from "@/lib/api";
import { Download, FileText, Cloud, FileDown } from "lucide-react";
import { Button } from "@/components/ui/button";

interface ComprobanteActions {
  onDownloadXML: (row: ComprobanteRow) => void;
  onDownloadRIDE: (row: ComprobanteRow) => void;
  onEmitir: (row: ComprobanteRow) => void;
}

export const getComprobantesColumns = (actions: ComprobanteActions): ColumnDef<ComprobanteRow>[] => [
  {
    accessorKey: "Vet_Num",
    header: "# Comprobante",
    cell: ({ row }) => <span className="font-medium">{row.original.Vet_Num}</span>,
  },
  {
    accessorKey: "Prs_Nom",
    header: "Cliente",
    cell: ({ row }) => <span>{row.original.Prs_Nom || "-"}</span>,
  },
  {
    accessorKey: "Prs_Ced",
    header: "Identificación",
    cell: ({ row }) => <span className="font-mono text-xs">{row.original.Prs_Ced || "-"}</span>,
  },
  {
    accessorKey: "Vet_Sys",
    header: "Fecha",
    cell: ({ row }) => {
      const dateStr = row.original.Vet_Sys;
      if (!dateStr) return "-";
      return new Date(dateStr).toLocaleDateString("es-EC", {
        year: "numeric", month: "2-digit", day: "2-digit",
        hour: "2-digit", minute: "2-digit"
      });
    },
  },
  {
    accessorKey: "Vet_Aut",
    header: "Electrónico",
    cell: ({ row }) => {
      const isAuth = row.original.Vet_Aut === "S";
      return (
        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold ${
          isAuth ? "bg-[#10b981]/10 text-[#10b981]" : "bg-[#F3F4F6] text-[#6B7280]"
        }`}>
          {isAuth ? "Sí" : "No"}
        </span>
      );
    },
  },
  {
    accessorKey: "Vet_Sri",
    header: "Autorización",
    cell: ({ row }) => (
      <span className="font-mono text-xs max-w-[160px] truncate block" title={row.original.Vet_Sri || ""}>
        {row.original.Vet_Sri || "-"}
      </span>
    ),
  },
  {
    accessorKey: "Vet_Obs",
    header: "Observación",
    cell: ({ row }) => (
      <span className="max-w-[200px] truncate block text-sm text-[#6B7280]" title={row.original.Vet_Obs || ""}>
        {row.original.Vet_Obs || "-"}
      </span>
    ),
  },
  {
    id: "actions",
    header: () => <div className="text-center">Acciones</div>,
    cell: ({ row }) => {
      const c = row.original;
      return (
        <div className="flex items-center gap-1 justify-center">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => actions.onDownloadXML(c)}
            className="h-8 w-8 p-0 text-[#6B7280] hover:text-[#111827] hover:bg-[#F3F4F6]"
            title="Descargar XML"
          >
            <Download className="h-4 w-4" />
          </Button>
          <Button
            variant="ghost"
            size="sm"
            onClick={() => actions.onDownloadRIDE(c)}
            className="h-8 w-8 p-0 text-[#6B7280] hover:text-[#111827] hover:bg-[#F3F4F6]"
            title="Descargar RIDE (PDF)"
          >
            <FileText className="h-4 w-4" />
          </Button>
          {c.Vet_Aut !== "S" && (
            <Button
              variant="ghost"
              size="sm"
              onClick={() => actions.onEmitir(c)}
              className="h-8 w-8 p-0 text-[#3b82f6] hover:text-[#2563eb] hover:bg-[#3b82f6]/10"
              title="Emitir (generar XML + autorizar SRI)"
            >
              <Cloud className="h-4 w-4" />
            </Button>
          )}
        </div>
      );
    },
  }
];

interface RetencionActions {
  onDownloadXML: (row: RetencionRow) => void;
  onDownloadRIDE: (row: RetencionRow) => void;
  onEmitir: (row: RetencionRow) => void;
}

export const getRetencionesColumns = (actions: RetencionActions): ColumnDef<RetencionRow>[] => [
  {
    accessorKey: "Ret_Num",
    header: "# Retención",
    cell: ({ row }) => <span className="font-medium">{row.original.Ret_Num}</span>,
  },
  {
    accessorKey: "Ret_Fec",
    header: "Fecha",
    cell: ({ row }) => {
      const dateStr = row.original.Ret_Fec;
      if (!dateStr) return "-";
      return new Date(dateStr).toLocaleDateString("es-EC", {
        year: "numeric", month: "2-digit", day: "2-digit"
      });
    },
  },
  {
    accessorKey: "Prs_Nom",
    header: "Proveedor",
    cell: ({ row }) => <span>{row.original.Prs_Nom || "-"}</span>,
  },
  {
    accessorKey: "Ret_Con",
    header: "Concepto",
    cell: ({ row }) => (
      <span className="max-w-[200px] truncate block text-sm" title={row.original.Ret_Con || ""}>
        {row.original.Ret_Con || "-"}
      </span>
    ),
  },
  {
    accessorKey: "Ret_Aut",
    header: "Electrónico",
    cell: ({ row }) => {
      const isAuth = row.original.Ret_Aut === "S";
      return (
        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold ${
          isAuth ? "bg-[#10b981]/10 text-[#10b981]" : "bg-[#F3F4F6] text-[#6B7280]"
        }`}>
          {isAuth ? "Sí" : "No"}
        </span>
      );
    },
  },
  {
    accessorKey: "Ret_Sri",
    header: "Autorización SRI",
    cell: ({ row }) => (
      <span className="font-mono text-xs max-w-[140px] truncate block" title={row.original.Ret_Sri || ""}>
        {row.original.Ret_Sri || "-"}
      </span>
    ),
  },
  {
    accessorKey: "Ret_Sys",
    header: "Registro",
    cell: ({ row }) => {
      const dateStr = row.original.Ret_Sys;
      if (!dateStr) return "-";
      return new Date(dateStr).toLocaleDateString("es-EC", {
        year: "numeric", month: "2-digit", day: "2-digit",
        hour: "2-digit", minute: "2-digit"
      });
    },
  },
  {
    id: "actions",
    header: () => <div className="text-center">Acciones</div>,
    cell: ({ row }) => {
      const c = row.original;
      return (
        <div className="flex items-center gap-1 justify-center">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => actions.onDownloadXML(c)}
            className="h-8 w-8 p-0 text-[#6B7280] hover:text-[#111827] hover:bg-[#F3F4F6]"
            title="Descargar XML"
          >
            <FileDown className="h-4 w-4" />
          </Button>
          <Button
            variant="ghost"
            size="sm"
            onClick={() => actions.onDownloadRIDE(c)}
            className="h-8 w-8 p-0 text-[#6B7280] hover:text-[#111827] hover:bg-[#F3F4F6]"
            title="Descargar RIDE (PDF)"
          >
            <FileText className="h-4 w-4" />
          </Button>
          {c.Ret_Aut !== "S" && (
            <Button
              variant="ghost"
              size="sm"
              onClick={() => actions.onEmitir(c)}
              className="h-8 w-8 p-0 text-[#3b82f6] hover:text-[#2563eb] hover:bg-[#3b82f6]/10"
              title="Sincronizar con SRI"
            >
              <Cloud className="h-4 w-4" />
            </Button>
          )}
        </div>
      );
    },
  }
];
