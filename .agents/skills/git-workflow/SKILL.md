---
name: git-workflow
description: >
  Activate this skill whenever committing code, creating or managing git branches,
  preparing pull requests, or structuring repository workflows. It defines the team's
  standards for Conventional Commits, branch naming conventions, atomic commit hygiene,
  pre-commit verification (Pint, tests, security checks), and pull request workflows.
---

# Git Workflow & Commit Conventions Guide

> **Purpose:** Standardize version control practices across the repository to ensure readable commit history, reliable changelogs, smooth code reviews, and reproducible releases.

---

## 1. Commit Message Convention (Conventional Commits)

All commit messages must follow the [Conventional Commits v1.0.0](https://www.conventionalcommits.org/) specification.

### Format

```text
<type>(<scope>): <short summary>

[optional body: detailed explanation of the change and rationale]

[optional footer(s): BREAKING CHANGE, Closes #123]
```

### Commit Types

| Type | When to Use | Example |
| :--- | :--- | :--- |
| `feat` | Adding a new user-facing or API feature | `feat(auth): implement passkey webauthn authentication` |
| `fix` | Bug fixes | `fix(auth): prevent session race condition on login` |
| `refactor` | Code restructuring without altering external API behavior | `refactor(repo): extract query filters to UserRepository` |
| `test` | Adding or updating tests without changing production code | `test(arch): convert test calls to pest arch expectations` |
| `perf` | Changes that improve performance or reduce query overhead | `perf(models): eager load user roles to prevent n+1 queries` |
| `docs` | Documentation only (markdown, API docs, comments) | `docs(readme): add setup instructions for local development` |
| `style` | Formatting, missing semi-colons, whitespace (Laravel Pint) | `style(dto): format register data according to pint rules` |
| `chore` | Maintenance tasks, tool updates, build config | `chore(deps): update laravel framework to v13.17` |
| `ci` | CI/CD configuration files and scripts | `ci(github): add workflow for running pest test suite` |
| `revert` | Reverting a previous commit | `revert: revert feat(auth): implement passkey webauthn` |

### Scopes (Repository-Specific)

Use concise, lowercase scopes matching project layers and domains:

- **Domains/Features:** `auth`, `passkey`, `socialite`, `user`, `billing`
- **Layers:** `controller`, `dto`, `repo`, `model`, `migration`, `resource`, `rule`, `middleware`, `enum`
- **Infrastructure:** `config`, `routes`, `deps`, `pint`, `pest`, `arch`

### Subject Line Rules

1. **Imperative, present tense:** Use `"add"`, not `"added"` or `"adds"`. Use `"fix"`, not `"fixed"`.
2. **Lowercase:** Start with a lowercase letter directly after the colon and space.
3. **No trailing punctuation:** Do not end the subject line with a period (`.`).
4. **Length limit:** Keep the subject line under **72 characters**.

### Body & Footers

- Use the body to explain **what** changed and **why**, not how (the diff shows the how).
- Separate the subject from the body with a blank line.
- Wrap body lines at 72–80 characters.
- **Breaking Changes:** Add `!` after the type/scope and include a `BREAKING CHANGE:` footer:
  ```text
  feat(auth)!: replace token-based auth with sanctum cookies

  BREAKING CHANGE: API endpoints now require cookie headers instead of Bearer tokens.
  ```

---

## 2. Branching Strategy

### Main Branches

- `main`: Production-ready, stable codebase. Commits on `main` must always pass tests and linting.
- Feature work is never committed directly to `main` without review.

### Supporting Branch Naming

Branch names must use lowercase kebab-case prefixed with the change category:

| Pattern | Usage | Example |
| :--- | :--- | :--- |
| `feat/<description>` | New features or API capabilities | `feat/passkey-authentication` |
| `fix/<description>` | Bug fixes | `fix/user-registration-validation` |
| `refactor/<description>` | Code cleanups and refactoring | `refactor/unify-dto-contracts` |
| `test/<description>` | Adding test coverage or arch tests | `test/auth-pest-architecture` |
| `chore/<description>` | Dependencies, configs, docs | `chore/update-readme-endpoints` |
| `hotfix/<description>` | Urgent production bug fixes | `hotfix/cors-preflight-handling` |

If tracking an issue/ticket:
`feat/GH-42-passkey-authentication` or `fix/issue-102-session-expiry`

---

## 3. Atomic Commits & Hygiene

1. **One Logical Change Per Commit:**
   - Keep commits small, focused, and self-contained.
   - Do not combine feature additions with unrelated refactoring or code formatting.
2. **Never Commit Secrets or Local State:**
   - Never stage `.env`, local SQLite files (`database/*.sqlite`), private keys, or credentials.
   - Verify `git status` before running `git commit`.
3. **Interactive Staging:**
   - Use `git add -p` to review hunks individually and stage only changes belonging to the intended commit.
4. **Clean Working Tree:**
   - Clean up scratch files, temporary logs, or dump files (`dd()`, `dump()`, `ray()`) before committing.

---

## 4. Pre-Commit Verification Checklist

Before staging and committing any changes, run the following verification steps:

```bash
# 1. Format PHP files to repository Pint standards
vendor/bin/pint --dirty --format agent

# 2. Run the test suite (must pass with 0 failures)
php artisan test --compact

# 3. Verify no lingering debug functions or uncommitted junk
git status
```

---

## 5. Pull Request & Merging Workflow

1. **Branch Update:** Rebase or merge `main` into your feature branch before opening a PR:
   ```bash
   git fetch origin
   git rebase origin/main
   ```
2. **PR Title:** Follow the Conventional Commits format (e.g., `feat(auth): add passkey authentication endpoints`).
3. **PR Description:** Include:
   - **Summary:** Concise high-level overview.
   - **Key Changes:** Bullet points describing core changes.
   - **Verification:** Commands and test outputs proving everything passes.
4. **Merge Method:**
   - **Squash and Merge:** Recommended for multi-commit feature branches to keep `main` history linear and clean.
   - **Rebase / Merge Commit:** For grouped multi-part milestones.
