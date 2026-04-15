# ─────────────────────────────────────────────────
# Merchandise Frontend — Multi-stage Dockerfile
# ─────────────────────────────────────────────────

# === Stage 1: Install dependencies ===
FROM node:20-alpine AS deps
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

# === Stage 2: Build the application ===
FROM node:20-alpine AS builder
WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
COPY . .

# Build-time env vars (override via docker build --build-arg)
ARG NEXT_PUBLIC_API_BASE_URL=http://kong:8000
ARG NEXT_PUBLIC_KEYCLOAK_URL=https://devsso.cubeone.in
ARG NEXT_PUBLIC_KEYCLOAK_REALM=Merchandise
ARG NEXT_PUBLIC_KEYCLOAK_CLIENT_ID=merchandise
ARG NEXT_PUBLIC_ENABLE_KEYCLOAK=true

ENV NEXT_PUBLIC_API_BASE_URL=$NEXT_PUBLIC_API_BASE_URL
ENV NEXT_PUBLIC_KEYCLOAK_URL=$NEXT_PUBLIC_KEYCLOAK_URL
ENV NEXT_PUBLIC_KEYCLOAK_REALM=$NEXT_PUBLIC_KEYCLOAK_REALM
ENV NEXT_PUBLIC_KEYCLOAK_CLIENT_ID=$NEXT_PUBLIC_KEYCLOAK_CLIENT_ID
ENV NEXT_PUBLIC_ENABLE_KEYCLOAK=$NEXT_PUBLIC_ENABLE_KEYCLOAK

RUN npm run build

# === Stage 3: Production runner ===
FROM node:20-alpine AS runner
WORKDIR /app

ENV NODE_ENV=production
ENV PORT=3000
ENV HOSTNAME="0.0.0.0"

RUN addgroup --system --gid 1001 nodejs
RUN adduser --system --uid 1001 nextjs

# Copy standalone output
COPY --from=builder /app/.next/standalone ./
COPY --from=builder /app/.next/static ./.next/static
COPY --from=builder /app/public ./public

USER nextjs

EXPOSE 3000

CMD ["node", "server.js"]
