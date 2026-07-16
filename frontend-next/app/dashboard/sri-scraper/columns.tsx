"use client";

import { ColumnDef } from "@tanstack/react-table";
import { JobState, sriScraperApi } from "@/lib/api/sri-scraper";
import { Progress } from "@/components/ui/progress";
import { Button } from "@/components/ui/button";
import { XCircle, Download } from "lucide-react";
import { toast } from "sonner";

export const columns: (onRefresh: () => void) => ColumnDef<JobState>[] = (onRefresh) => [
  {
    accessorKey: "created_at",
    header: "Fecha de Creación",
    cell: ({ row }) => {
      return (
        <span className="font-medium text-[#4B5563]">
          {new Date(row.getValue("created_at")).toLocaleString()}
        </span>
      );
    },
  },
  {
    accessorKey: "status",
    header: "Estado del Job",
    cell: ({ row }) => {
      const status = row.original.status;
      const message = row.original.message;
      return (
        <div>
          <span
            className={`px-2.5 py-1 rounded-[6px] text-[11px] font-bold tracking-wide border ${
              status === "completed"
                ? "bg-[#10b981]/10 text-[#10b981] border-[#10b981]/20"
                : status === "error"
                ? "bg-[#EF4444]/10 text-[#EF4444] border-[#EF4444]/20"
                : status === "running"
                ? "bg-[#3b82f6]/10 text-[#3b82f6] border-[#3b82f6]/20 shadow-sm shadow-[#3b82f6]/10"
                : "bg-[#F3F4F6] text-[#6B7280] border-[#E5E7EB]"
            }`}
          >
            {status === "running"
              ? "En progreso"
              : status === "completed"
              ? "Completado"
              : status === "error"
              ? "Error"
              : status}
          </span>
          <div
            className="text-xs text-[#6B7280] mt-1.5 truncate max-w-[200px]"
            title={message}
          >
            {message}
          </div>
        </div>
      );
    },
  },
  {
    accessorKey: "progress",
    header: "Progreso de Descarga",
    cell: ({ row }) => {
      const job = row.original;
      return (
        <div className="space-y-1.5 w-full max-w-[200px]">
          <div className="flex justify-between text-xs text-[#6B7280] font-medium">
            <span>{job.progress}%</span>
            <span>
              {job.files_downloaded} / {job.total_files} docs
            </span>
          </div>
          <Progress value={job.progress} className="h-2 bg-[#F3F4F6]" />
        </div>
      );
    },
  },
  {
    id: "actions",
    header: () => <div className="text-right">Acciones</div>,
    cell: ({ row }) => {
      const job = row.original;
      
      const handleCancel = async () => {
        try {
          const res = await sriScraperApi.cancelJob(job.id);
          if (res.success) {
            toast.success("Trabajo cancelado correctamente");
            onRefresh();
          }
        } catch (error: any) {
          toast.error(error.message || "Error al cancelar el trabajo");
        }
      };

      return (
        <div className="text-right">
          {job.status === "running" && (
            <Button
              variant="ghost"
              size="sm"
              className="text-[#EF4444] hover:text-[#DC2626] hover:bg-[#EF4444]/10 rounded-[6px]"
              onClick={handleCancel}
            >
              <XCircle className="h-4 w-4" />
            </Button>
          )}
          {job.status === "completed" && (
            <Button
              variant="ghost"
              size="sm"
              title="Descargar ZIP"
              className="text-[#6B7280] hover:text-[#111827] rounded-[6px]"
              disabled
            >
              <Download className="h-4 w-4" />
            </Button>
          )}
        </div>
      );
    },
  },
];
