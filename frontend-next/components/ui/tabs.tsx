'use client';

import { Tabs as TabsPrimitive } from "@base-ui/react/tabs";
import { cn } from "@/lib/utils";

function TabsRoot({
  className,
  ...props
}: TabsPrimitive.Root.Props) {
  return (
    <TabsPrimitive.Root
      className={cn("w-full", className)}
      {...props}
    />
  );
}

function TabsList({
  className,
  ...props
}: TabsPrimitive.List.Props) {
  return (
    <TabsPrimitive.List
      className={cn(
        "inline-flex h-9 items-center gap-1 rounded-xl bg-muted p-1 text-muted-foreground",
        className
      )}
      {...props}
    />
  );
}

function TabsTab({
  className,
  ...props
}: TabsPrimitive.Tab.Props) {
  return (
    <TabsPrimitive.Tab
      className={cn(
        "inline-flex items-center justify-center whitespace-nowrap rounded-lg px-3 py-1 text-sm font-medium outline-none transition-all",
        "data-[active]:bg-background data-[active]:text-foreground data-[active]:shadow-sm",
        "hover:text-foreground",
        "focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring",
        "disabled:pointer-events-none disabled:opacity-50",
        className
      )}
      {...props}
    />
  );
}

function TabsPanel({
  className,
  ...props
}: TabsPrimitive.Panel.Props) {
  return (
    <TabsPrimitive.Panel
      className={cn("mt-4 focus-visible:outline-none", className)}
      {...props}
    />
  );
}

export {
  TabsRoot as Tabs,
  TabsList,
  TabsTab,
  TabsPanel,
};
