"use client";

import { useState } from "react";
import { format } from "date-fns";
import { es } from "date-fns/locale";
import { Calendar as CalendarIcon, Download } from "lucide-react";
import { toast } from "sonner";
import { motion } from "framer-motion";

import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Calendar } from "@/components/ui/calendar";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";

import { generateInvoicePDF } from "@/lib/pdf/generateInvoice";

export default function DashboardActions() {
  const [date, setDate] = useState<Date>();

  const handleDownload = () => {
    toast.info("Ensamblando factura PDF en tiempo real...", {
      description: "Generando documento con jsPDF.",
    });
    setTimeout(() => {
      generateInvoicePDF({
        companyName: "EXA CONTABLE S.A.",
        companyRuc: "1790000000001",
        invoiceNumber: "001-001-000012345",
        date: new Date(),
        clientName: "Juan Pérez",
        clientId: "1712345678",
        clientEmail: "juan.perez@example.com",
        items: [
          { description: "Licencia ERP EXA Contable - Plan Premium", quantity: 1, price: 120.00, total: 120.00 },
          { description: "Soporte Técnico Especializado (Horas)", quantity: 5, price: 25.00, total: 125.00 },
          { description: "Módulo SRI Scraper - Suscripción Anual", quantity: 1, price: 90.00, total: 90.00 }
        ],
        subtotal: 335.00,
        tax: 50.25,
        total: 385.25
      }, true);
      toast.success("Factura descargada correctamente.");
    }, 1500);
  };

  return (
    <div className="flex items-center gap-3 w-full sm:w-auto">
      <Popover>
        <PopoverTrigger asChild>
          <motion.div whileHover={{ scale: 1.02 }} whileTap={{ scale: 0.98 }} transition={{ type: "spring", stiffness: 400, damping: 17 }}>
            <Button
              variant={"outline"}
              className={cn(
                "w-full sm:w-[220px] justify-start text-left font-normal border-border/60 bg-card shadow-sm hover:bg-muted/50 hover:text-primary rounded-xl transition-all duration-200",
                !date && "text-muted-foreground"
              )}
            >
              <CalendarIcon className="mr-2 h-4 w-4" aria-hidden="true" />
              {date ? format(date, "PPP", { locale: es }) : <span>Filtrar por fecha</span>}
            </Button>
          </motion.div>
        </PopoverTrigger>
        <PopoverContent className="w-auto p-0 border-border/60 rounded-xl shadow-card-elevated" align="end">
          <Calendar
            mode="single"
            selected={date}
            onSelect={(newDate) => {
              setDate(newDate);
              if (newDate) {
                toast.success("Filtro aplicado", {
                  description: `Mostrando datos desde ${format(newDate, "PP", { locale: es })}`,
                });
              }
            }}
            className="rounded-xl"
          />
        </PopoverContent>
      </Popover>

      <motion.div whileHover={{ scale: 1.02 }} whileTap={{ scale: 0.95 }} transition={{ type: "spring", stiffness: 400, damping: 10 }}>
        <Button
          onClick={handleDownload}
          className="bg-primary hover:bg-primary/90 text-primary-foreground shadow-md shadow-primary/20 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-primary/25 w-full sm:w-auto rounded-xl group"
        >
          <Download className="mr-2 h-4 w-4 transition-transform duration-300 group-hover:-translate-y-0.5" aria-hidden="true" />
          Reporte
        </Button>
      </motion.div>
    </div>
  );
}
