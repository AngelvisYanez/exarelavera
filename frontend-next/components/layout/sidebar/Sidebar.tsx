"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { cn } from "@/lib/utils";
import { FullLogo } from "@/components/layout/shared/logo/FullLogo";
import { sidebarItems, type SidebarItem } from "./Sidebaritems";
import {
  ChevronDown,
} from "lucide-react";
import { useState } from "react";
import SimpleBar from "simplebar-react";
import "simplebar-react/dist/simplebar.min.css";

interface SidebarProps {
  onClose?: () => void;
  inSheet?: boolean;
}

export function Sidebar({ onClose, inSheet }: SidebarProps) {
  const pathname = usePathname();
  const [openMenus, setOpenMenus] = useState<Record<string, boolean>>({});

  const toggleMenu = (title: string) => {
    setOpenMenus((prev) => ({ ...prev, [title]: !prev[title] }));
  };

  const isActive = (href?: string) => {
    if (!href) return false;
    if (href === "/dashboard") return pathname === "/dashboard";
    return pathname.startsWith(href);
  };

  const hasActiveChild = (item: SidebarItem): boolean => {
    if (item.children) return item.children.some((c) => isActive(c.href));
    return false;
  };

  const renderItem = (item: SidebarItem, depth = 0) => {
    const active = isActive(item.href);
    const childActive = hasActiveChild(item);
    const isOpen = openMenus[item.title] ?? childActive;

    if (item.children) {
      return (
        <div key={item.title} className="px-3">
          <button
            onClick={() => toggleMenu(item.title)}
            className={cn(
              "flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2",
              childActive
                ? "bg-lightprimary text-primary"
                : "text-link hover:bg-lightprimary hover:text-primary",
            )}
          >
            {item.iconComponent && <item.iconComponent className="h-5 w-5 shrink-0" />}
            <span className="flex-1 text-left">{item.title}</span>
            <ChevronDown
              className={cn(
                "h-4 w-4 transition-transform duration-200",
                isOpen && "rotate-180",
              )}
            />
          </button>
          {isOpen && (
            <div className="mt-1 space-y-1 pl-4">
              {item.children.map((child) => (
                <Link
                  key={child.title}
                  href={child.href || "#"}
                  onClick={onClose}
                  className={cn(
                    "flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2",
                    isActive(child.href)
                      ? "bg-lightprimary text-primary"
                      : "text-link hover:bg-lightprimary hover:text-primary",
                  )}
                >
                  <span className="h-1.5 w-1.5 rounded-full bg-current shrink-0" />
                  {child.title}
                </Link>
              ))}
            </div>
          )}
        </div>
      );
    }

    return (
      <div key={item.title} className="px-3">
        <Link
          href={item.href || "#"}
          onClick={onClose}
          className={cn(
            "flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2",
            active
              ? "bg-primary text-white shadow-md shadow-primary/30"
              : "text-link hover:bg-lightprimary hover:text-primary",
          )}
        >
          {item.iconComponent && <item.iconComponent className="h-5 w-5 shrink-0" />}
          <span>{item.title}</span>
          {item.badge && (
            <span
              className={cn(
                "ml-auto badge",
                item.badgeColor || "bg-primary text-white",
              )}
            >
              {item.badge}
            </span>
          )}
        </Link>
      </div>
    );
  };

  return (
    <aside className={cn("flex h-full w-full flex-col bg-white", !inSheet && "border-r border-border")}>
      <div className="flex items-center justify-center px-6 py-5 border-b border-border shrink-0">
        <FullLogo />
      </div>
      <SimpleBar className="flex-1 overflow-x-hidden">
        <nav className="sidebar-nav pt-3 pb-8 space-y-1">
          {sidebarItems.map((item, idx) => {
            if (!item) return null;
            if ("subheading" in item) {
              return (
                <div key={idx} className="px-6 pt-4 pb-2">
                  <span className="text-xs font-bold uppercase tracking-wider text-sidebar-muted">
                    {item.subheading}
                  </span>
                </div>
              );
            }
            return renderItem(item as SidebarItem);
          })}
        </nav>
      </SimpleBar>
      <div className="p-4 border-t border-border shrink-0">
        <div className="rounded-lg bg-gradient-to-r from-primary to-blue-600 p-4 text-white text-center">
          <p className="text-xs font-bold uppercase tracking-wider">EXA Contable</p>
          <p className="text-[10px] opacity-80 mt-1">Sistema de Gestión</p>
        </div>
      </div>
    </aside>
  );
}
