/**
 * Formbricks is enabled only when a non-empty workspace id is configured.
 *
 * `VITE_FORMBRICKS_WORKSPACE_ID` mirrors the server-side `FORMBRICKS_WORKSPACE_ID`
 * via `"${FORMBRICKS_WORKSPACE_ID}"`, so an unset value resolves to an empty string
 * rather than `undefined` - hence the truthiness check rather than a `typeof` one.
 */
export function formbricksEnabled(): boolean {
    return Boolean(import.meta.env.VITE_FORMBRICKS_WORKSPACE_ID);
}
