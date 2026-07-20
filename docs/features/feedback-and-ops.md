# Feedback & Ops

## What it is

Two independent, optional integrations, both off by default:

- **Formbricks feedback survey**: an in-app survey users can trigger to send feedback, plus a webhook endpoint that receives survey responses.
- **Discord ops relay**: forwards incoming Formbricks survey responses to a Discord channel via webhook, so the team sees feedback without polling Formbricks directly.

Both are hidden and inert until their environment variables are set; neither requires code changes to enable.

## Prerequisites

- A Formbricks workspace (self-hosted or `app.formbricks.com`), if you want the survey.
- A Discord server with an incoming webhook configured, if you want the ops relay. The relay only forwards Formbricks responses; it isn't a general-purpose notifier yet.

## Formbricks: the env vars

```env
# FORMBRICKS_APP_URL=https://app.formbricks.com
# FORMBRICKS_WORKSPACE_ID=
# FORMBRICKS_WEBHOOK_SECRET=

# VITE_FORMBRICKS_WORKSPACE_ID="${FORMBRICKS_WORKSPACE_ID}"
# VITE_FORMBRICKS_APP_URL="${FORMBRICKS_APP_URL}"
```

All commented out (unset) in `.env.example`. On the frontend, `formbricksEnabled()` (`resources/js/lib/formbricks.ts`) checks whether `VITE_FORMBRICKS_WORKSPACE_ID` is a non-empty string; when it's unset, the survey trigger simply doesn't initialize. `FORMBRICKS_WEBHOOK_SECRET` is used server-side to verify the signature on incoming webhooks (`webhook.signature:formbricks` middleware on `POST /webhooks/formbricks`), independent of whether the frontend widget is enabled.

## Discord ops relay: the env vars

```env
# DISCORD_OPS_WEBHOOK_URL=
# DISCORD_OPS_ENABLED=false
```

`DISCORD_OPS_ENABLED` is the actual gate: `FormbricksController::handle` checks `config('services.discord.ops_enabled')` first and returns immediately (204, no-op) if it's falsy, before doing anything else with the incoming payload. When enabled, a formatted version of the Formbricks response is queued (`ProcessFormbricksResponse`) and posted to `DISCORD_OPS_WEBHOOK_URL` by `DiscordOpsNotifier`. If the URL is empty even though the flag is on, the notifier logs a warning and skips the send rather than failing.

## How to verify

- With both integrations unset: the frontend never loads the Formbricks widget, and `POST /webhooks/formbricks` returns `204` without dispatching anything.
- With `FORMBRICKS_WORKSPACE_ID` and the matching `VITE_FORMBRICKS_*` set: the feedback survey trigger becomes available in the app.
- With `DISCORD_OPS_ENABLED=true` and a valid `DISCORD_OPS_WEBHOOK_URL`: a real Formbricks survey response should appear as a message in the configured Discord channel shortly after submission.

Never commit real values for `FORMBRICKS_WEBHOOK_SECRET` or `DISCORD_OPS_WEBHOOK_URL`; keep them in your local `.env` or the host's secret store only.

See [Configuration](/configuration) for the full environment variable reference.
