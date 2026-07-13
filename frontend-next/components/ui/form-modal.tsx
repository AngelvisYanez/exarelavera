"use client";

import { Loader2 } from "lucide-react";
import { cn } from "@/lib/utils";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { AlertCircle } from "lucide-react";

interface FormModalProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: string;
  description?: string;
  children: React.ReactNode;
  onSubmit?: () => void;
  loading?: boolean;
  error?: string | null;
  submitLabel?: string;
  cancelLabel?: string;
  maxWidth?: string;
}

export function FormModal({
  open,
  onOpenChange,
  title,
  description,
  children,
  onSubmit,
  loading = false,
  error = null,
  submitLabel = "Guardar",
  cancelLabel = "Cancelar",
  maxWidth = "max-w-md",
}: FormModalProps) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className={cn(maxWidth, "max-h-[90vh] overflow-y-auto")}>
        <DialogHeader>
          <DialogTitle>{title}</DialogTitle>
          {description && <DialogDescription>{description}</DialogDescription>}
        </DialogHeader>

        {error && (
          <Alert variant="destructive">
            <AlertCircle className="h-4 w-4" />
            <AlertDescription>{error}</AlertDescription>
          </Alert>
        )}

        <form onSubmit={(e) => { e.preventDefault(); onSubmit?.(); }} className="space-y-4">
          {children}

          <div className="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2 pt-2 border-t">
            <Button
              type="button"
              variant="outline"
              onClick={() => onOpenChange(false)}
              disabled={loading}
            >
              {cancelLabel}
            </Button>
            {onSubmit && (
              <Button type="submit" disabled={loading}>
                {loading && <Loader2 className="h-4 w-4 animate-spin mr-1" />}
                {submitLabel}
              </Button>
            )}
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
}
