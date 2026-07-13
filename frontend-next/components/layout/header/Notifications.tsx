"use client";

import { useState } from "react";
import { Bell } from "lucide-react";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
  DropdownMenuGroup,
} from "@/components/ui/dropdown-menu";
import { buttonVariants } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import { notifications } from "./Data";

export function Notifications() {
  const [unread] = useState(notifications.length);

  return (
    <DropdownMenu>
      <DropdownMenuTrigger
        className={cn(buttonVariants({ variant: "ghost", size: "icon" }), "relative rounded-full")}
      >
        <Bell className="h-5 w-5 text-link" />
        {unread > 0 && (
          <span className="absolute -top-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-error text-[10px] font-bold text-white">
            {unread}
          </span>
        )}
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-80">
        <DropdownMenuGroup>
          <DropdownMenuLabel>Notificaciones</DropdownMenuLabel>
        </DropdownMenuGroup>
        <DropdownMenuSeparator />
        {notifications.length === 0 ? (
          <div className="px-4 py-8 text-center text-sm text-muted-foreground">
            No hay notificaciones
          </div>
        ) : (
          notifications.map((notif, i) => (
            <DropdownMenuItem key={i} className="cursor-pointer py-3">
              <div className="flex items-start gap-3">
                <div className={cn("mt-0.5 h-2 w-2 shrink-0 rounded-full", notif.color || "bg-primary")} />
                <div className="space-y-0.5">
                  <p className="text-sm font-medium">{notif.title}</p>
                  <p className="text-xs text-muted-foreground">{notif.subtitle}</p>
                </div>
              </div>
            </DropdownMenuItem>
          ))
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
