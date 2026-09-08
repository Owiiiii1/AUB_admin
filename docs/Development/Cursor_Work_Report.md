# Cursor Work Report

## Task

Зафиксировать обязательное правило: любое изменение `AUB_admin` сразу выкладывается на production. Выложить актуальные `docs/` на сервер. Flutter / `.env` / `vendor` / `node_modules` / storage не трогались.

## Production

- **GitHub:** `origin/main` (`docs: require immediate AUB_admin production deploy` после commit)
- **Server:** `deploy@178.156.234.23:/var/www/aub/docs` скопирован целиком (scp)
- **Не заливалось:** `.env`, `vendor`, `node_modules`, storage, Flutter, PHP/JS/миграции

## Files changed (repo)

### Created

- `.cursor/rules/aub-admin-deploy.mdc`

### Modified

- `.gitignore` (track `.cursor/rules/`)
- `docs/en/DEVELOPMENT_RULES.md`
- `docs/ru/DEVELOPMENT_RULES.md`
- `docs/en/SERVER_DEPLOYMENT.md`
- `docs/ru/SERVER_DEPLOYMENT.md`
- `docs/en/ARCHITECTURE.md`
- `docs/ru/ARCHITECTURE.md`
- `docs/Development/Cursor_Work_Report.md`

## Checks

- SSH `deploy@178.156.234.23` работает
- После copy: на сервере есть обновлённые docs (DECIDED Class / deploy rule)
- Non-docs application code: none
