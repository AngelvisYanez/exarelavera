import { cn } from "@/lib/utils";
import { Card, CardContent } from "@/components/ui/card";

interface CardBoxProps {
  children: React.ReactNode;
  className?: string;
}

export function CardBox({ children, className }: CardBoxProps) {
  return (
    <Card className={cn("bg-background shadow-boxShadow border-ld", className)}>
      <CardContent className="p-6">{children}</CardContent>
    </Card>
  );
}
