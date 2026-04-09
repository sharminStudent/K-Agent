# Git Workflow For K-Agent

This file is a practical guide for using Git on this project.

## 1. Core Commands

Run these in the project root:

```bash
git status
git add .
git commit -m "your message"
git push
```

## 2. What Each Command Means

| Command | What it does |
|---|---|
| `git status` | Shows changed, new, or deleted files |
| `git add .` | Stages your current changes for commit |
| `git commit -m "message"` | Saves a checkpoint in Git history |
| `git push` | Sends your commits to GitHub |

## 3. Recommended Daily Workflow

1. Open the project.
2. Run `git status`.
3. Do one small task or one module step.
4. Run `git status` again.
5. Run `git add .`.
6. Run `git commit -m "clear message"`.
7. Run `git push`.

## 4. Good Commit Message Examples

| Situation | Commit message |
|---|---|
| Base setup complete | `Initial Laravel 13 setup` |
| Schema added | `Add core K-Agent database schema` |
| Models added | `Add agent chat and lead models` |
| Services created | `Create service layer skeleton` |
| Filament installed | `Install Filament admin panel` |
| Agent settings built | `Add K-Agent settings module` |
| Knowledge upload built | `Add knowledge file upload flow` |

## 5. When To Commit

Commit when:
- one task is complete
- one file group is stable
- a module milestone is done
- before trying a risky refactor

Do not wait until everything is finished.

## 6. Safe Rules

- Commit small changes often.
- Read `git status` before committing.
- Use clear commit messages.
- Push often so GitHub becomes your backup.
- Do not use `git reset --hard` unless you fully understand it.

## 7. Branch Workflow Later

Once the project grows, use branches for features:

```bash
git checkout -b feature/agent-schema
```

After the feature is done:

```bash
git add .
git commit -m "Add agent schema"
git push -u origin feature/agent-schema
```

## 8. Current Repo State

At the time this file was added:
- branch: `main`
- remote: not connected yet
- existing commit: `Initial Laravel 13 setup`

## 9. Next Git Step

Connect this local repo to a GitHub remote, then push:

```bash
git remote add origin <your-github-repo-url>
git push -u origin main
```
