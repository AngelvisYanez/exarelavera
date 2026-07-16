import { jsPDF } from "jspdf";
import "jspdf-autotable";
import { format } from "date-fns";
import { es } from "date-fns/locale";

interface InvoiceData {
  companyName: string;
  companyRuc: string;
  invoiceNumber: string;
  date: Date;
  clientName: string;
  clientId: string;
  clientEmail?: string;
  items: {
    description: string;
    quantity: number;
    price: number;
    total: number;
  }[];
  subtotal: number;
  tax: number;
  total: number;
}

export const generateInvoicePDF = (data: InvoiceData, download: boolean = true) => {
  // Create a new jsPDF instance (A4 size)
  const doc = new jsPDF("p", "pt", "a4");
  
  // Colors (Infrastructure Workload Tuning Theme)
  const primaryColor = "#EF4444"; // Red 500
  const textColor = "#111827"; // Gray 900
  const lightGray = "#6B7280"; // Gray 500
  
  // Header
  doc.setFillColor(primaryColor);
  doc.rect(0, 0, doc.internal.pageSize.width, 120, "F");
  
  doc.setTextColor("#FFFFFF");
  doc.setFontSize(24);
  doc.setFont("helvetica", "bold");
  doc.text(data.companyName, 40, 60);
  
  doc.setFontSize(10);
  doc.setFont("helvetica", "normal");
  doc.text(`RUC: ${data.companyRuc}`, 40, 80);
  
  // Invoice Details (Right aligned in Header)
  doc.setFontSize(20);
  doc.setFont("helvetica", "bold");
  doc.text("FACTURA", doc.internal.pageSize.width - 40, 60, { align: "right" });
  
  doc.setFontSize(10);
  doc.setFont("helvetica", "normal");
  doc.text(`Nº: ${data.invoiceNumber}`, doc.internal.pageSize.width - 40, 80, { align: "right" });
  doc.text(`Fecha: ${format(data.date, "dd MMM yyyy", { locale: es })}`, doc.internal.pageSize.width - 40, 95, { align: "right" });

  // Client Info Section
  doc.setTextColor(textColor);
  doc.setFontSize(12);
  doc.setFont("helvetica", "bold");
  doc.text("Facturado a:", 40, 160);
  
  doc.setFontSize(10);
  doc.setFont("helvetica", "normal");
  doc.setTextColor(lightGray);
  doc.text(data.clientName, 40, 180);
  doc.text(`RUC / CI: ${data.clientId}`, 40, 195);
  if (data.clientEmail) {
    doc.text(`Email: ${data.clientEmail}`, 40, 210);
  }

  // Items Table
  const tableData = data.items.map(item => [
    item.description,
    item.quantity.toString(),
    `$${item.price.toFixed(2)}`,
    `$${item.total.toFixed(2)}`
  ]);

  // @ts-ignore - jspdf-autotable adds autoTable to jsPDF instance
  doc.autoTable({
    startY: 250,
    head: [["Descripción", "Cant.", "Precio Unit.", "Total"]],
    body: tableData,
    theme: 'plain',
    headStyles: {
      fillColor: [249, 250, 251], // Gray 50
      textColor: [17, 24, 39], // Gray 900
      fontStyle: 'bold',
      lineWidth: 0,
      cellPadding: 8
    },
    bodyStyles: {
      textColor: [107, 114, 128], // Gray 500
      cellPadding: 8
    },
    columnStyles: {
      0: { cellWidth: 260 },
      1: { halign: 'center' },
      2: { halign: 'right' },
      3: { halign: 'right' }
    },
    didDrawCell: (data: any) => {
      // Add bottom border to rows
      if (data.row.section === 'body') {
        doc.setDrawColor(229, 231, 235); // Gray 200
        doc.setLineWidth(0.5);
        doc.line(
          data.cell.x,
          data.cell.y + data.cell.height,
          data.cell.x + data.cell.width,
          data.cell.y + data.cell.height
        );
      }
    }
  });

  // Totals Section
  // @ts-ignore
  const finalY = doc.lastAutoTable.finalY + 30;
  
  doc.setFontSize(10);
  doc.setTextColor(textColor);
  
  const rightColumnX = doc.internal.pageSize.width - 40;
  const labelsX = rightColumnX - 100;
  
  doc.setFont("helvetica", "normal");
  doc.text("Subtotal:", labelsX, finalY, { align: "right" });
  doc.text(`$${data.subtotal.toFixed(2)}`, rightColumnX, finalY, { align: "right" });
  
  doc.text("IVA (15%):", labelsX, finalY + 20, { align: "right" });
  doc.text(`$${data.tax.toFixed(2)}`, rightColumnX, finalY + 20, { align: "right" });
  
  // Total Line
  doc.setDrawColor(229, 231, 235);
  doc.setLineWidth(1);
  doc.line(labelsX - 20, finalY + 30, rightColumnX, finalY + 30);
  
  doc.setFont("helvetica", "bold");
  doc.setFontSize(12);
  doc.setTextColor(primaryColor);
  doc.text("Total:", labelsX, finalY + 50, { align: "right" });
  doc.text(`$${data.total.toFixed(2)}`, rightColumnX, finalY + 50, { align: "right" });

  // Footer
  const pageHeight = doc.internal.pageSize.height;
  doc.setFontSize(8);
  doc.setTextColor(lightGray);
  doc.setFont("helvetica", "normal");
  doc.text("Generado por EXA Contable - Sistema ERP Premium", doc.internal.pageSize.width / 2, pageHeight - 40, { align: "center" });

  if (download) {
    doc.save(`Factura_${data.invoiceNumber}.pdf`);
  } else {
    // Return the blob URL to display in an iframe (preview mode)
    return doc.output("bloburl");
  }
};
