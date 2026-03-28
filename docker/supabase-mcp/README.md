# Supabase MCP Server - Docker

Docker image for the [Supabase MCP Server](https://github.com/supabase-community/supabase-mcp) to use with Docker MCP Toolkit.

## Build

```bash
docker build -t supabase-mcp:latest ./docker/supabase-mcp/
```

## Configuration

### Environment Variables

| Variable | Required | Description |
|---|---|---|
| `SUPABASE_ACCESS_TOKEN` | Yes | Personal Access Token from [Supabase Dashboard](https://supabase.com/dashboard/account/tokens) |

### CLI Arguments (passed via args)

| Argument | Required | Description |
|---|---|---|
| `--project-ref` | No | Scope to a specific Supabase project |
| `--features` | No | Comma-separated list of feature groups to enable |
| `--read-only` | No | Restrict to read-only operations |

### Feature Groups

- `account` - Project and organization management
- `database` - SQL execution, migrations, tables
- `docs` - Supabase documentation search
- `debugging` - Logs and advisors
- `development` - Project URLs, API keys, TypeScript types
- `functions` - Edge Functions management
- `branching` - Database branching
- `storage` - Storage bucket management (disabled by default)

## Docker MCP Toolkit Setup

### 1. Build the image

```bash
docker build -t supabase-mcp:latest ./docker/supabase-mcp/
```

### 2. Add to MCP Toolkit profile

Edit `~/.docker/mcp/profiles.json`:

```json
{
  "profiles": {
    "qrmadearmando": {
      "servers": [
        {
          "id": "supabase-mcp",
          "image": "supabase-mcp:latest",
          "config": {
            "env": {
              "SUPABASE_ACCESS_TOKEN": "${SUPABASE_ACCESS_TOKEN}"
            },
            "args": ["--project-ref", "<your-project-ref>"]
          }
        }
      ]
    }
  }
}
```

### 3. Set your token

```bash
export SUPABASE_ACCESS_TOKEN="sbp_your_token_here"
```

Get your token from: https://supabase.com/dashboard/account/tokens

## Alternative: HTTP Remote (No Docker needed)

If you prefer not to run locally, use the hosted version:

```json
{
  "mcpServers": {
    "supabase": {
      "type": "http",
      "url": "https://mcp.supabase.com/mcp"
    }
  }
}
```

This uses OAuth authentication (will prompt you to log in via browser).
