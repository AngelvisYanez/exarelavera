"use client";

import { useState, useCallback, type ReactNode } from "react";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";

interface ConfirmState {
  open: boolean;
  title: string;
  description?: string;
}

interface UseConfirmReturn {
  confirm: (title: string, description?: string) => Promise<boolean>;
  ConfirmDialog: ReactNode;
}

export function useConfirm(): UseConfirmReturn {
  const [state, setState] = useState<ConfirmState>({ open: false, title: "" });
  const [resolver, setResolver] = useState<((value: boolean) => void) | null>(null);

  const confirm = useCallback((title: string, description?: string): Promise<boolean> => {
    return new Promise<boolean>((resolve) => {
      setState({ open: true, title, description });
      setResolver(() => resolve);
    });
  }, []);

  const handleAction = useCallback(() => {
    setState((prev) => ({ ...prev, open: false }));
    resolver?.(true);
    setResolver(null);
  }, [resolver]);

  const handleCancel = useCallback(() => {
    setState((prev) => ({ ...prev, open: false }));
    resolver?.(false);
    setResolver(null);
  }, [resolver]);

  const ConfirmDialog = (
    <AlertDialog open={state.open} onOpenChange={(open) => { if (!open) handleCancel(); }}>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>{state.title}</AlertDialogTitle>
          {state.description && (
            <AlertDialogDescription>{state.description}</AlertDialogDescription>
          )}
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel onClick={handleCancel}>Cancelar</AlertDialogCancel>
          <AlertDialogAction onClick={handleAction}>Confirmar</AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );

  return { confirm, ConfirmDialog };
}
