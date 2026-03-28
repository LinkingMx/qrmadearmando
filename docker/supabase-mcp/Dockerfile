FROM node:22-alpine

LABEL org.opencontainers.image.title="Supabase MCP Server"
LABEL org.opencontainers.image.description="MCP server for Supabase - database management, project management, and more"
LABEL org.opencontainers.image.source="https://github.com/supabase-community/supabase-mcp"

RUN npm install -g @supabase/mcp-server-supabase@latest

ENTRYPOINT ["mcp-server-supabase"]
