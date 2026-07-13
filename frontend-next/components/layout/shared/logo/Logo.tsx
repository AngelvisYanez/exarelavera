import Image from "next/image";
import Link from "next/link";

export function Logo() {
  return (
    <Link href="/dashboard">
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
    </Link>
  );
}
