const hits = new Map<string, { count: number; resetAt: number }>();

const WINDOW_MS = 60_000;
const MAX_HITS = 60;

export function checkRateLimit(ip: string): { allowed: boolean; remaining: number } {
  const now = Date.now();
  const entry = hits.get(ip);

  if (!entry || now > entry.resetAt) {
    hits.set(ip, { count: 1, resetAt: now + WINDOW_MS });
    return { allowed: true, remaining: MAX_HITS - 1 };
  }

  entry.count++;

  if (entry.count > MAX_HITS) {
    return { allowed: false, remaining: 0 };
  }

  return { allowed: true, remaining: MAX_HITS - entry.count };
}

export function getClientIp(request: Request): string {
  const forwarded = request.headers.get("x-forwarded-for");
  if (forwarded) return forwarded.split(",")[0].trim();
  return "127.0.0.1";
}
