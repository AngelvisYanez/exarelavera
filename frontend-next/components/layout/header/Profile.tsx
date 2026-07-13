"use client";

import Link from "next/link";
import { User, LogOut, Settings } from "lucide-react";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuGroup,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { buttonVariants } from "@/components/ui/button";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { cn } from "@/lib/utils";

interface ProfileProps {
  user?: { usuario: string; Bdd: string };
  onLogout: () => void;
}

export function Profile({ user, onLogout }: ProfileProps) {
  const initials = user?.usuario?.slice(0, 2).toUpperCase() || "EX";

  return (
    <DropdownMenu>
      <DropdownMenuTrigger
        className={cn(buttonVariants({ variant: "ghost", size: "icon" }), "rounded-full")}
      >
        <Avatar className="h-8 w-8">
          <AvatarFallback className="bg-lightprimary text-primary text-xs font-bold">
            {initials}
          </AvatarFallback>
        </Avatar>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-56">
        <DropdownMenuGroup>
          <DropdownMenuLabel>
            <div className="flex flex-col space-y-1">
              <p className="text-sm font-medium leading-none">{user?.usuario || "Usuario"}</p>
              <p className="text-xs leading-none text-muted-foreground">
                {user?.Bdd || "EXA Relavera"}
              </p>
            </div>
          </DropdownMenuLabel>
        </DropdownMenuGroup>
        <DropdownMenuSeparator />
        <DropdownMenuItem className="cursor-pointer">
          <Link href="/dashboard/incidencias" className="flex items-center">
            <Settings className="mr-2 h-4 w-4" />
            <span>Configuración BD</span>
          </Link>
        </DropdownMenuItem>
        <DropdownMenuItem onClick={onLogout} className="text-destructive focus:text-destructive cursor-pointer">
          <LogOut className="mr-2 h-4 w-4" />
          <span>Cerrar sesión</span>
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
