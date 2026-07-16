"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import {
  Calculator,
  Calendar,
  CreditCard,
  Settings,
  Smile,
  User,
  FileText,
  Package,
  Car,
  Briefcase
} from "lucide-react";

import {
  CommandDialog,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
  CommandSeparator,
  CommandShortcut,
} from "@/components/ui/command";

export function CommandMenu({ open, setOpen }: { open: boolean, setOpen: (open: boolean) => void }) {
  const router = useRouter();

  useEffect(() => {
    const down = (e: KeyboardEvent) => {
      if (e.key === "k" && (e.metaKey || e.ctrlKey)) {
        e.preventDefault();
        setOpen(true);
      }
    };
    document.addEventListener("keydown", down);
    return () => document.removeEventListener("keydown", down);
  }, [setOpen]);

  const runCommand = (command: () => void) => {
    setOpen(false);
    command();
  };

  return (
    <CommandDialog open={open} onOpenChange={setOpen}>
      <CommandInput placeholder="Busca un comando o módulo..." />
      <CommandList>
        <CommandEmpty>No se encontraron resultados.</CommandEmpty>
        
        <CommandGroup heading="Módulos Principales">
          <CommandItem onSelect={() => runCommand(() => router.push("/dashboard/facturacion"))}>
            <FileText className="mr-2 h-4 w-4" />
            <span>Facturación</span>
          </CommandItem>
          <CommandItem onSelect={() => runCommand(() => router.push("/dashboard/inventario"))}>
            <Package className="mr-2 h-4 w-4" />
            <span>Inventario & Stock</span>
          </CommandItem>
          <CommandItem onSelect={() => runCommand(() => router.push("/dashboard/transporte"))}>
            <Car className="mr-2 h-4 w-4" />
            <span>Transporte de Carga</span>
          </CommandItem>
          <CommandItem onSelect={() => runCommand(() => router.push("/dashboard/rrhh"))}>
            <Briefcase className="mr-2 h-4 w-4" />
            <span>Recursos Humanos</span>
          </CommandItem>
        </CommandGroup>
        
        <CommandSeparator />
        
        <CommandGroup heading="Acciones Rápidas">
          <CommandItem onSelect={() => runCommand(() => router.push("/dashboard/facturacion/emitir"))}>
            <FileText className="mr-2 h-4 w-4 text-primary" />
            <span>Emitir Nueva Factura</span>
            <CommandShortcut>⌘+E</CommandShortcut>
          </CommandItem>
          <CommandItem onSelect={() => runCommand(() => router.push("/dashboard/caja-chica"))}>
            <CreditCard className="mr-2 h-4 w-4" />
            <span>Registrar Gasto (Caja Chica)</span>
          </CommandItem>
        </CommandGroup>
        
        <CommandSeparator />
        
        <CommandGroup heading="Ajustes">
          <CommandItem onSelect={() => runCommand(() => router.push("/dashboard/admin"))}>
            <Settings className="mr-2 h-4 w-4" />
            <span>Administración</span>
            <CommandShortcut>⌘+S</CommandShortcut>
          </CommandItem>
        </CommandGroup>
      </CommandList>
    </CommandDialog>
  );
}
