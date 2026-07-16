"use client";

import { useTheme } from "next-themes";
import { Moon, Sun } from "lucide-react";
import { Button } from "@/components/ui/button";
import { useEffect, useState } from "react";

export function ThemeToggle() {
  const { theme, setTheme } = useTheme();
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
  }, []);

  if (!mounted) {
    return (
      <Button variant="ghost" size="icon" className="w-9 h-9 opacity-50 cursor-default">
        <span className="visually-hidden">Cambiar tema oscuro/claro</span>
      </Button>
    );
  }

  return (
    <Button
      variant="ghost"
      size="icon"
      onClick={() => setTheme(theme === "dark" ? "light" : "dark")}
      className="w-9 h-9 hover:bg-muted/50 rounded-full transition-all duration-300 hover:scale-105 active:scale-95"
      aria-label="Alternar tema oscuro"
    >
      {theme === "dark" ? (
        <Sun className="h-4 w-4 text-warning" aria-hidden="true" />
      ) : (
        <Moon className="h-4 w-4 text-foreground" aria-hidden="true" />
      )}
      <span className="visually-hidden">Cambiar tema oscuro/claro</span>
    </Button>
  );
}
