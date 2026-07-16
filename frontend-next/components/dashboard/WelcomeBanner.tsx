"use client";

import { useAuth } from "@/lib/auth-context";
import { CalendarDays, Sparkles } from "lucide-react";
import { motion } from "framer-motion";

function getGreeting(): string {
  const hour = new Date().getHours();
  if (hour < 12) return "Buenos días";
  if (hour < 18) return "Buenas tardes";
  return "Buenas noches";
}

function getFormattedDate(): string {
  return new Date().toLocaleDateString("es-ES", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}

export default function WelcomeBanner() {
  const { user } = useAuth();
  const greeting = getGreeting();
  const dateStr = getFormattedDate();

  return (
    <div className="relative overflow-hidden rounded-[2rem] p-8 sm:p-12 mb-20 shadow-2xl shadow-primary/10 border border-white/10 dark:border-white/5 bg-gradient-to-br from-sidebar-accent via-background to-sidebar-accent isolate">
      {/* Dynamic Background Mesh (Orbs) */}
      <motion.div 
        animate={{ 
          scale: [1, 1.2, 1],
          opacity: [0.3, 0.5, 0.3],
          rotate: [0, 90, 0]
        }}
        transition={{ duration: 20, repeat: Infinity, ease: "linear" }}
        className="absolute -top-1/2 -right-1/4 w-[800px] h-[800px] rounded-full bg-primary/10 dark:bg-primary/20 blur-3xl pointer-events-none -z-10" 
      />
      <motion.div 
        animate={{ 
          scale: [1, 1.5, 1],
          opacity: [0.2, 0.4, 0.2],
        }}
        transition={{ duration: 15, repeat: Infinity, ease: "easeInOut" }}
        className="absolute -bottom-1/2 -left-1/4 w-[600px] h-[600px] rounded-full bg-blue-500/10 dark:bg-blue-500/20 blur-3xl pointer-events-none -z-10" 
      />
      <div className="absolute inset-0 noise-texture opacity-30 mix-blend-overlay pointer-events-none -z-10" />

      <div className="relative z-10 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-6 pb-4 sm:pb-8">
        <div className="max-w-2xl">
          <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2, type: "spring", stiffness: 300, damping: 20 }}
            className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary/10 dark:bg-primary/20 text-primary dark:text-sky-400 text-xs font-bold uppercase tracking-widest mb-6 border border-primary/20"
          >
            <Sparkles className="h-3.5 w-3.5" />
            <span>Sistema En Línea</span>
          </motion.div>
          
          <h1 className="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-foreground tracking-tight leading-none font-display">
            {greeting},<br/>
            <span className="text-transparent bg-clip-text bg-gradient-to-r from-primary to-blue-600 dark:from-sky-400 dark:to-blue-500">
              {user?.usuario || "Usuario"}
            </span>
          </h1>
          <p className="mt-4 text-lg text-muted-foreground max-w-lg font-medium">
            Resumen de actividad y estado financiero del sistema ERP EXA Contable.
          </p>
        </div>
        
        <div className="flex flex-col items-start sm:items-end gap-3 shrink-0">
          <div className="flex items-center gap-2 text-foreground/80 font-medium px-4 py-2.5 rounded-xl bg-background/50 dark:bg-black/20 backdrop-blur-md border border-border/50 shadow-sm">
            <CalendarDays className="h-5 w-5 text-primary" aria-hidden="true" />
            <p className="text-sm capitalize">{dateStr}</p>
          </div>
        </div>
      </div>
    </div>
  );
}
