# K-Agent System Specifications

This document reflects the current confirmed direction of the `k-agent` project as a SaaS platform and distinguishes implemented scope from planned scope.

## 1. Product Direction

K-Agent is being built as a SaaS platform where each company manages its own AI agent configuration, knowledge base, leads, and chat history. The current repository is not a finished SaaS platform yet. It is at the backend foundation stage.

## 2. Current Confirmed Architecture

### 2.1 Platform Type

- Multi-tenant SaaS by company ownership model.
- The tenant boundary is currently represented by `agent_id`.
- A user belongs to one company agent.
- Core business records are scoped to the owning agent/company.

### 2.2 Proposal Tech Alignment

The original project proposal defines the intended technology direction. The current repository does not fully match that proposal yet.

Proposal tech stack:

| Technology | Proposal Expectation | Current Repo Status | Alignment |
|---|---|---|---|
| Laravel | Core backend framework | Present | Aligned |
| PHP | Core backend language | Present | Aligned |
| PostgreSQL | Primary relational database | Present | Aligned |
| Tailwind CSS 4 | Frontend styling | Present | Aligned |
| Laravel Herd | Local PHP/Nginx/Node environment | Not represented in repo docs or runtime config | Environment choice only |
| Node.js | Frontend/tooling runtime | Implicitly required for Vite/npm | Partially aligned |
| Livewire 4 | Reactive frontend stack for proposal-aligned widget flows | Implemented for the widget frame | Aligned |
| Alpine.js | Widget/client interaction layer | Implemented for the widget frame | Aligned |
| Laravel Reverb | WebSocket/realtime streaming layer | Installed and wired into the widget runtime path | Partially aligned |
| WebSockets | Realtime streaming transport | Wired into the widget runtime path | Partially aligned |
| OpenAI | LLM and embeddings provider | Implemented | Aligned |
| Text-Embedding-3 | Embedding model family | Implemented via configurable OpenAI embeddings | Aligned |
| Vector Database | Company knowledge vector storage | Implemented with Qdrant or file fallback | Aligned |
| Railway | Deployment target | Not implemented in repo | Not aligned |
| Continuous Deployment via GitHub | Automated deployment pipeline | Not implemented in repo | Not aligned |
| Visual Studio Code | Developer tool | Not repo-enforced | Informational |
| Vite | Frontend build tool | Present in repo but not explicitly named in proposal | Additional current tech |

Current practical reading:

- The repo already matches the proposal on Laravel, PHP, PostgreSQL, Tailwind, OpenAI, embeddings, and vector database direction.
- The repo now matches the proposal on Livewire 4 and Alpine.js for the widget/runtime path.
- The repo now uses Laravel Reverb and WebSockets for the widget/runtime path, though the current behavior is realtime delivery of completed assistant messages rather than full token-by-token streaming.
- The repo does not yet match the proposal on Railway deployment or GitHub-based continuous deployment.
- Qdrant remains acceptable as the vector database implementation for the proposal's vector storage requirement.

### 2.3 Proposal Tech Placement

To avoid further ambiguity, the table below defines where each proposal-required technology belongs in this project.

| Proposal Technology | Required In This Project | Exact Project Area | Why It Belongs There | Current State |
|---|---|---|---|---|
| Laravel Herd | Yes | Local development environment and onboarding documentation | The proposal defines Herd as the local PHP/Nginx/Node environment for running the app during development | Not documented |
| Livewire 4 | Yes | Interactive widget-facing Laravel UI | Applied to the embeddable widget flow without replacing the existing Filament dashboard | Implemented |
| Alpine.js | Yes | Widget-side client interaction layer | Applied in the embeddable widget for lightweight frontend behavior and local state | Implemented |
| Laravel Reverb | Yes | Realtime chat runtime | Installed and used to deliver widget assistant updates over the realtime channel | Implemented for widget runtime |
| WebSockets | Yes | Transport for streaming responses between backend and widget | Used in the widget runtime transport path through Reverb/Echo | Implemented for widget runtime |
| Railway | Yes | Deployment and production hosting layer | The proposal explicitly defines Railway as the deployment target | Not implemented |
| GitHub Continuous Deployment | Yes | Deployment automation pipeline | The proposal explicitly requires automated deployment from source control into production | Not implemented |

Practical system mapping:

- `Laravel Herd` belongs in setup documentation, local environment instructions, and team development standards.
- `Livewire 4` belongs in the embeddable chat/widget experience and related widget-facing Laravel UI.
- `Alpine.js` belongs inside the widget frontend for lightweight state, open/close behavior, composer state, panels, and client-side interaction handling.
- `Laravel Reverb` and `WebSockets` belong in the chat delivery layer so assistant responses can stream incrementally instead of waiting for full-message polling responses.
- `Railway` belongs in deployment configuration, environment variable strategy, service topology, and production rollout documentation.
- `GitHub Continuous Deployment` belongs in CI/CD workflow files and deployment automation.
- The existing Filament dashboard should remain in place and should not be refactored away for proposal alignment work unless a later requirement explicitly demands it.

### 2.4 Proposal Tech Not Yet Used In Code

The following proposal technologies are still not implemented in the codebase or project deployment setup:

- `Railway` deployment
- `GitHub Continuous Deployment`

Clarification:

- `Railway` deployment and `GitHub Continuous Deployment` are missing from the deployment and operations side of the project.

### 2.5 Implemented Core Data Domains

- `agents`
- `chat_sessions`
- `chat_messages`
- `leads`
- `knowledge_files`
- `users.agent_id`

### 2.6 Implemented Ownership and Access Rules

- Authenticated users can create one company agent when they do not already own one.
- Users can only view or update their own agent configuration.
- Users can only regenerate widget tokens for their own agent.
- Public widget-facing flows resolve the company by `widget_token`.
- Chat, lead, and knowledge records are stored against the resolved `agent_id`.

## 3. Implemented Backend Capabilities

### 3.0 Filament Admin

Implemented:

- Filament admin panel and authentication
- Custom branded login screen
- Tenant-scoped sidebar/navigation structure
- Dashboard with widgets for setup, company stats, conversation trends, account snapshot, recent chats, recent leads, and workspace users
- Agent settings section
- Leads section
- Chat logs section with transcript viewer/download
- Knowledge management section
- General Settings section with Profile view and Profile edit flow

Not implemented:

- Dashboard preferences page for configurable user settings
- Rich reporting/export features across dashboard sections
- Full create/upload workflows from Filament for every domain

### 3.1 Agent Configuration API

Implemented:

- Create agent
- View agent
- Update agent
- Regenerate widget token

Available company/agent fields currently include:

- Agent name
- Company name
- Slug
- Website URL
- Industry
- Company description
- Contact email
- Support email
- Support phone
- System prompt
- Welcome message
- Fallback message
- Settings JSON
- Active status

### 3.2 Chat Backend

Implemented:

- Create chat session by `widget_token`
- Store visitor message by `widget_token` and session public id
- Persist chat sessions and user messages

Not implemented:

- Streaming responses
- Conversation quality scoring
- Reverb/WebSocket-based realtime delivery from the proposal stack

### 3.3 Lead Capture

Implemented:

- Lead storage endpoint
- Agent-scoped lead persistence
- Optional session linkage
- Filament lead management UI with view/edit/filter support

Not implemented:

- Lead qualification workflow

### 3.4 Knowledge Upload and Processing

Implemented:

- Upload endpoint
- File validation
- File storage
- Metadata persistence
- Text extraction for TXT, CSV, JSON, and DOCX
- Chunk generation
- Ingestion status transitions: `pending`, `processing`, `ready`, `failed`
- Filament knowledge review UI

Not implemented:

- Livewire-based knowledge workflows from the proposal stack

## 4. Confirmed Missing Major SaaS Areas

The following areas are not yet implemented in the current repository:

- Subscription and billing model
- Role model beyond the current single-owner pattern
- Guardrails and fallback analytics integrated into runtime behavior
- Railway deployment setup
- GitHub-based continuous deployment pipeline
- Deployment strategy and operations plan

## 5. Current SaaS Interpretation

The correct reading of current progress is:

- The project already has tenant-aware backend groundwork.
- The project already has a working Filament admin/dashboard shell for internal/company use.
- The project already has an AI runtime, embeddings flow, retrieval, and a working widget shell.
- The project now uses the proposal's intended Livewire/Alpine/Reverb/WebSocket stack for the widget/runtime path while keeping Filament intact.
- The current realtime behavior delivers assistant messages through the Reverb/WebSocket path, but deeper token-level streaming can still be improved later.
- The repository is suitable for continuing toward a SaaS platform, but it should not be described as feature-complete or launch-ready.

## 6. Verified Testing Status

The current feature test suite passes locally.

Covered areas:

- Agent API
- Chat API
- Lead API
- Knowledge API
- Widget web flows
- Admin/Filament flows

Current local result at verification time:

- `42` tests passed
- `166` assertions passed

## 7. Recommended Next Build Order

To move from backend foundation to actual SaaS platform, the next implementation order should be:

1. Update project documentation and tracker files to reflect the current Filament/admin state.
2. Keep the existing OpenAI + Qdrant RAG backend and remove documentation drift around what is already implemented.
3. Harden and refine the Livewire + Alpine widget flow without replacing the current Filament dashboard.
4. Extend the existing Reverb/WebSocket widget runtime from completed-message delivery toward richer token-level streaming if required.
5. Standardize Laravel Herd as the documented local development environment.
6. Add stronger guardrails, fallback tracking, and platform logging.
7. Define Railway deployment and GitHub continuous deployment strategy.

## 8. Notes

- The repository does not currently contain a prior `k-agent-system-specification.md` file.
- This file now reflects both the current repo state and the original proposal technology requirements.
- Qdrant is retained as the acceptable vector database implementation for the proposal's vector storage requirement.
