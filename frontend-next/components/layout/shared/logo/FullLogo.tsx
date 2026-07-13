import Image from "next/image";
import Link from "next/link";

export function FullLogo() {
  return (
    <Link href="/dashboard" className="flex items-center gap-2">
      <Image
        src="/images/logos/logo-icon.svg"
        width={40}
        height={40}
        alt="logo"
        className=""
      />
      <Image
        src="/images/logos/logo-icon-dark.svg"
        width={40}
        height={40}
        alt="logo"
        className="hidden"
      />
      <span className="text-xl font-bold text-dark">EXA Relavera</span>
    </Link>
  );
}
