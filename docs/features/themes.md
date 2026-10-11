# Themes

## What it is

A theme changes the app's primary color: the color of main buttons, links, focus rings, progress bars and the first chart series. Each user can keep up to five themes and use one at a time. With no theme in use, the app shows its built-in violet.

Themes apply to the signed-in app only. The landing page and the sign-in, registration, password reset, email verification and two-factor pages always use the built-in colors. The logo keeps its own colors under every theme.

## Data model

A `Theme` belongs to a user (`themes` table, deleted with the account):

| Column        | Meaning                                                                                           |
| ------------- | ------------------------------------------------------------------------------------------------- |
| `name`        | Chosen by the user, at most 50 characters. [Encrypted](/features/encryption) like other user text |
| `light_color` | Primary color in light mode, a lowercase `#rrggbb` hex value                                      |
| `dark_color`  | Primary color in dark mode. `null` means the light color is used in both modes                    |
| `is_active`   | Whether this is the theme in use. At most one per user                                            |

The limit is `Theme::MAX_PER_USER`. `Theme::activate()` puts a theme in use and takes the others of the same user out of use in one transaction. Deleting the theme in use leaves none active, so the app falls back to the built-in colors.

## How a theme is applied

The app's colors are CSS variables declared in `resources/css/app.css`. The focus ring, the sidebar accent, the first chart color and the navigation progress bar all refer to `--primary`, so overriding that one variable on the root element recolors the app.

`App\Services\Themes\ThemeStylesheet` builds that override for the current request:

```css
html:root {
    --primary: #2563eb;
    --primary-foreground: #ffffff;
}
html.dark {
    --primary: #93c5fd;
    --primary-foreground: #111111;
}
```

It returns `null` when nobody is signed in, when the user has no theme in use, or when the route is one of the unthemed pages listed in the class.

`HandleInertiaRequests` shares the result as the `themeStylesheet` prop. It reaches the browser twice:

- **On a full page load**, `resources/views/app.blade.php` prints it inside `<style id="user-theme">` in the document head, so the first paint already has the right color.
- **On every Inertia visit**, `resources/js/lib/userTheme.ts` copies the prop into that same element. Selecting another theme, or moving between a themed and an unthemed page, updates the color without a reload.

Charts read their color when they render, so a chart already on screen keeps its color until the page is visited again.

## Text on the primary color

Any color is accepted. To keep button labels readable, `App\Services\Themes\ContrastingTextColor` computes the relative luminance of the color (the WCAG formula) and picks near-black text above a luminance of 0.179, white below. That value is where black and white text give the same contrast ratio.

The same calculation exists in `resources/js/lib/contrasting-text-color.ts` for the live preview in the theme form. The two must stay identical.

## Validation

`App\Rules\ThemeRules` holds the rules shared by `StoreThemeRequest` and `UpdateThemeRequest`. Colors must match `#rrggbb` exactly. The stylesheet builder checks the same pattern again before writing a value into CSS, and returns nothing if it does not match.

## Routes

All under the `auth` and `verified` middleware, in `routes/settings.php`:

| Method | URI                                 | Action                                  |
| ------ | ----------------------------------- | --------------------------------------- |
| GET    | `/settings/appearance`              | Appearance page, with the user's themes |
| POST   | `/settings/themes`                  | Create a theme and put it in use        |
| PUT    | `/settings/themes/{theme}`          | Rename or recolor a theme               |
| PATCH  | `/settings/themes/{theme}/activate` | Use a theme                             |
| PATCH  | `/settings/themes/default`          | Go back to the built-in theme           |
| DELETE | `/settings/themes/{theme}`          | Delete a theme                          |

A theme that belongs to another user answers 404.
