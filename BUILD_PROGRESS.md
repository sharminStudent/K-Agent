# K-Agent Build Progress

Use this file as the project tracker for the actual `k-agent` repo.

Status values:
- `Done`
- `In Progress`
- `Not Started`
- `Blocked`

## 1. Foundation

| ID | Task | Status | Notes |
|---|---|---|---|
| 1.1 | Laravel project created | Done | Base Laravel app exists |
| 1.2 | PHP version aligned with spec (PHP 8.4) | Not Started | Intentional deviation: project kept on PHP 8.3 |
| 1.3 | PostgreSQL configured | Done | `.env` uses PostgreSQL and migrations ran successfully |
| 1.4 | `.env` configured for K-Agent services | In Progress | `.env` exists, project-specific setup still missing |
| 1.5 | Vite installed and working | Done | Present |
| 1.6 | Tailwind installed and working | Done | Present |
| 1.7 | Base app runs locally | Done | Backend app and test suite run locally |
| 1.8 | Project README updated for K-Agent | Not Started | Still mostly default Laravel README |

## 1A. Proposal Tech Alignment

| ID | Task | Status | Notes |
|---|---|---|---|
| 1A.1 | Laravel used as core backend framework | Done | Core application is built on Laravel |
| 1A.2 | PHP used as core backend language | Done | Application runs on PHP |
| 1A.3 | PostgreSQL used as main relational database | Done | `.env` and `.env.testing` use PostgreSQL |
| 1A.4 | Tailwind CSS used for frontend styling | Done | Tailwind is installed and used |
| 1A.5 | Node.js frontend/tooling runtime available | Done | `npm`/Vite workflow is present |
| 1A.6 | OpenAI used for chat and embeddings | Done | Chat completion and embeddings are implemented |
| 1A.7 | Text-Embedding-3 family configured for embeddings | Done | OpenAI embedding model is configurable and used |
| 1A.8 | Vector database used for RAG storage | Done | Qdrant is implemented and remains acceptable for the proposal |
| 1A.9 | Livewire 4 used for proposal-required widget interaction flows | Done | Widget frame now mounts through a Livewire component while leaving Filament intact |
| 1A.10 | Alpine.js used for proposal-required widget client behavior | Done | Widget interaction/state is now Alpine-driven |
| 1A.11 | Laravel Reverb used for widget realtime chat streaming | In Progress | Reverb is installed and wired for realtime widget delivery; deeper token-level streaming can still be expanded |
| 1A.12 | WebSocket streaming implemented for widget delivery | In Progress | Widget runtime now uses the WebSocket/Reverb path for assistant delivery, though streaming is not yet token-by-token |
| 1A.13 | Railway deployment target prepared | Not Started | Proposal defines Railway as the deployment platform |
| 1A.14 | GitHub-based continuous deployment prepared | Not Started | Proposal defines automated deployment through GitHub integration |
| 1A.15 | Laravel Herd documented as standard local development environment | Not Started | Proposal expects Herd for local development setup and this should be reflected in project docs |
| 1A.16 | Visual Studio Code documented as the recommended editor | Not Started | Proposal mentions VS Code; this is documentation-only, not an application requirement |

## 1B. Proposal Tech Placement Map

| ID | Task | Status | Notes |
|---|---|---|---|
| 1B.1 | Laravel Herd mapped to local setup and onboarding docs | Not Started | Must be documented in setup instructions rather than implemented in app code |
| 1B.2 | Livewire 4 mapped to proposal-aligned widget-facing Laravel UI | Done | Widget-related flows now use Livewire without refactoring the existing Filament dashboard |
| 1B.3 | Alpine.js mapped to widget frontend behavior | Done | Widget frontend behavior is now Alpine-driven |
| 1B.4 | Laravel Reverb mapped to widget chat runtime streaming layer | Done | Reverb-backed event broadcasting is now part of the widget runtime path |
| 1B.5 | WebSockets mapped to widget/backend realtime transport | Done | Widget/backend realtime transport path is now in place |
| 1B.6 | Railway mapped to deployment topology | Not Started | Must be reflected in deployment config and production documentation |
| 1B.7 | GitHub CD mapped to deployment automation | Not Started | Must be reflected in workflow automation and release process |

## 2. Admin / Filament

| ID | Task | Status | Notes |
|---|---|---|---|
| 2.1 | Filament installed | Done | `filament/filament` is installed in Composer |
| 2.2 | Admin authentication configured | Done | Admin panel uses Filament auth middleware and custom login page |
| 2.3 | `app/Filament` structure created | Done | Resources, widgets, auth page, and panel provider exist |
| 2.4 | "K-Agent Settings" page/resource exists | Done | `AgentResource` provides tenant-scoped agent settings |
| 2.5 | Dashboard home page with reports/cards/client tables exists | In Progress | Dashboard loads with setup, stats, trends, workspace, recent chats, recent leads, and workspace user widgets; richer reporting and deeper client management are still incomplete |
| 2.6 | Leads dashboard section with tables/statistics exists | In Progress | `LeadResource` exists with tenant-scoped table/filter/edit support and dashboard widgets surface recent lead activity, but export and deeper reporting remain incomplete |
| 2.7 | Chat logs section with full conversation viewer exists | Done | `ChatSessionResource` includes session list, transcript viewer, and transcript download |
| 2.8 | Agent management section exists | In Progress | Agent/company settings, branding uploads, and provider credential management exist, but future agent features and activity logs are still missing |
| 2.9 | Company profile settings page exists | Done | Distinct profile view/edit flow exists under General Settings |
| 2.10 | Knowledge management section exists | In Progress | `KnowledgeFileResource` exists for tenant-scoped review and inspection, but add/edit/upload management flow is still limited from Filament |
| 2.11 | Dashboard preference/settings section exists | In Progress | Light/dark mode is functioning in Filament, but dedicated preference settings UI is still missing |

## 3. Data Schema

| ID | Task | Status | Notes |
|---|---|---|---|
| 3.1 | `agents` migration created | Done | Implemented and migrated |
| 3.2 | `chat_sessions` migration created | Done | Implemented and migrated |
| 3.3 | `chat_messages` migration created | Done | Implemented and migrated |
| 3.4 | `leads` migration created | Done | Implemented and migrated |
| 3.5 | `knowledge_files` migration created | Done | Implemented and migrated |
| 3.6 | All main tables linked to `agent_id` | Done | Foreign keys added where required |
| 3.7 | Indexes added for common queries | Done | Core indexes added on session, message, lead, and knowledge tables |

## 4. Models

| ID | Task | Status | Notes |
|---|---|---|---|
| 4.1 | `Agent` model created | Done | Implemented |
| 4.2 | `ChatSession` model created | Done | Implemented |
| 4.3 | `ChatMessage` model created | Done | Implemented |
| 4.4 | `Lead` model created | Done | Implemented |
| 4.5 | `KnowledgeFile` model created | Done | Implemented |
| 4.6 | Relationships defined correctly | Done | Base relationships added |

## 5. Service Layer

| ID | Task | Status | Notes |
|---|---|---|---|
| 5.1 | `app/Services` directory created | Done | `ChatService` added |
| 5.2 | `AgentService` created | Done | Added for company agent config and widget token handling |
| 5.3 | `KnowledgeIngestionService` created | Done | `KnowledgeService` now handles upload, extraction, and chunk preparation |
| 5.4 | `EmbeddingService` created | Done | OpenAI embedding generation is wired into knowledge ingestion and retrieval with company-level credential support |
| 5.5 | `VectorStoreService` created | Done | Qdrant-backed vector persistence and query flow are implemented with safe file-backed fallback |
| 5.6 | `RetrievalService` created | Done | Retrieval is wired into chat and prefers Qdrant similarity search when configured |
| 5.7 | `ChatService` created | Done | Session creation and message persistence implemented |
| 5.8 | `LeadService` created | Done | Company-scoped lead storage implemented |
| 5.9 | `GuardrailService` created | In Progress | Runtime fallback is wired into chat, but the guardrail remains minimal and does not yet score groundedness or log fallback events |
| 5.10 | Controllers kept thin | In Progress | API controllers delegate core behavior to services, but formatting and some endpoint orchestration still live in controllers |
| 5.11 | Filament contains no business logic | In Progress | Filament resources/pages are mostly UI-layer only, but widget/query composition still lives in the panel layer |

## 6. Agent Configuration

| ID | Task | Status | Notes |
|---|---|---|---|
| 6.1 | Agent/company settings schema implemented | Done | SaaS-oriented company fields and agent settings endpoints added |
| 6.2 | `widget_token` generation implemented | Done | Auto-generated in `Agent` model and regeneratable via `AgentService` |
| 6.3 | SaaS company ownership model implemented | Done | Users belong to an `agent_id`, policies enforce company ownership, and core records are agent-scoped |
| 6.4 | Agent settings editable in admin | Done | `AgentResource` allows admin create/view/update for the tenant-owned agent |

## 7. Knowledge Management

| ID | Task | Status | Notes |
|---|---|---|---|
| 7.1 | Knowledge upload endpoint exists | Done | Implemented at `/api/knowledge/upload` |
| 7.2 | File validation rules implemented | Done | File type and size validation added |
| 7.3 | File storage configured | Done | Files stored per company agent on configured filesystem disk |
| 7.4 | File metadata saved in PostgreSQL | Done | Upload metadata saved in `knowledge_files` |
| 7.5 | Knowledge linked to `agent_id` | Done | Uploads resolve company by widget token and store `agent_id` |
| 7.6 | Filament knowledge management UI exists | Done | `KnowledgeFileResource` provides tenant-scoped knowledge file management UI |

## 8. Ingestion / Embeddings

| ID | Task | Status | Notes |
|---|---|---|---|
| 8.1 | Text extraction implemented | Done | TXT, CSV, JSON, and DOCX extraction added |
| 8.2 | Chunking implemented | Done | Extracted text is chunked and stored as processing artifacts |
| 8.3 | OpenAI embeddings integrated | Done | Knowledge ingestion generates embeddings through OpenAI using platform or company-provided credentials |
| 8.4 | Vector DB storage integrated | Done | Qdrant integration is implemented for vector upsert and query, with local file fallback when unavailable |
| 8.5 | Ingestion status tracking exists | Done | Knowledge files transition through pending, processing, ready, failed |
| 8.6 | Embeddings stored outside PostgreSQL | Done | Embeddings are stored in Qdrant when configured and otherwise persisted as filesystem artifacts rather than PostgreSQL rows |

## 9. Chat Backend

| ID | Task | Status | Notes |
|---|---|---|---|
| 9.1 | `/chat/session` endpoint exists | Done | Implemented at `/api/chat/session` |
| 9.2 | `/chat/send-message` endpoint exists | Done | Implemented at `/api/chat/send-message` |
| 9.3 | Session creation logic implemented | Done | `ChatService` creates sessions by widget token |
| 9.4 | Chat messages persisted | Done | User messages stored in `chat_messages` |
| 9.5 | Chat data linked to session and agent | Done | Session and message writes are agent-scoped |
| 9.6 | API validation added | Done | Form request validation added |

## 10. RAG

| ID | Task | Status | Notes |
|---|---|---|---|
| 10.1 | Agent resolved by `widget_token` | Done | Base agent lookup implemented in `ChatService` |
| 10.2 | Retrieval from vector DB implemented | Done | Chat retrieval can query Qdrant by company-scoped embeddings |
| 10.3 | OpenAI chat integration implemented | Done | Assistant response generation is connected end-to-end with OpenAI and fallback handling |
| 10.4 | Prompt constrained to company data | In Progress | Prompt builder uses company identity and retrieved company context, but constraint quality still needs hardening and broader evaluation |
| 10.5 | Responses grounded in retrieved context | In Progress | Retrieved chunks are included in prompt construction and empty-context requests fall back, but groundedness quality still needs stronger evaluation and enforcement |

## 11. Guardrails

| ID | Task | Status | Notes |
|---|---|---|---|
| 11.1 | Insufficient-knowledge detection exists | In Progress | Runtime falls back when no relevant context is retrieved, but detection is still a simple empty-context check |
| 11.2 | Fallback response defined | Done | Agent/company fallback message fields exist and are used in runtime when context or provider execution fails |
| 11.3 | Fallback queries logged | Not Started | Missing |
| 11.4 | Hallucination-risk behavior handled | Not Started | Missing |

## 12. Leads

| ID | Task | Status | Notes |
|---|---|---|---|
| 12.1 | Lead intent flow designed | Not Started | Missing |
| 12.2 | `/lead/store` endpoint exists | Done | Implemented at `/api/lead/store` |
| 12.3 | Lead validation implemented | Done | Form request validation added |
| 12.4 | Leads stored in DB | Done | Leads now persist via `LeadService` |
| 12.5 | Leads linked to session and agent | Done | Lead storage is agent-scoped and session-aware |
| 12.6 | Lead admin UI exists | Done | `LeadResource` provides tenant-scoped lead management UI |
| 12.7 | Lead export works | Done | CSV export exists from the Filament leads page |

## 13. Chat Logs

| ID | Task | Status | Notes |
|---|---|---|---|
| 13.1 | Chat sessions visible in admin | Done | `ChatSessionResource` lists tenant-scoped chat sessions |
| 13.2 | Full conversation logs view exists | Done | Session infolist renders the message transcript |
| 13.3 | Lead-to-session linking visible in UI | In Progress | Lead and session resources expose session/leads counts, but richer cross-linking is still limited |
| 13.4 | Transcript download/export works | Done | Transcript download route and controller are wired |
| 13.5 | Conversation quality/rating indicator exists | Not Started | Average/good/etc. status still to be designed |

## 14. Widget

| ID | Task | Status | Notes |
|---|---|---|---|
| 14.1 | iframe widget architecture started | Done | Embed script, iframe frame, preview, and widget routes exist |
| 14.2 | Widget bootstrap using token exists | Done | Widget bootstrap endpoint exists and is tested |
| 14.3 | Widget sends messages to backend | Done | Widget posts to chat and lead endpoints |
| 14.4 | Widget displays responses | Done | Widget renders assistant replies and errors |
| 14.5 | Session continuity handled | In Progress | Existing session bootstrap and local archive/session restore exist, but proposal-aligned realtime continuity is incomplete |
| 14.6 | Alpine.js widget UI implemented | Done | Widget interaction layer is now Alpine-based |
| 14.7 | Livewire 4 integrated into proposal-aligned widget/application interaction flow | Done | Widget flow is now mounted through Livewire while the existing Filament dashboard remains unchanged |

## 15. Realtime

| ID | Task | Status | Notes |
|---|---|---|---|
| 15.1 | Laravel Reverb installed | Done | Reverb package is installed |
| 15.2 | Broadcast/event setup configured | Done | Broadcasting config, channels route, and widget assistant event path are wired |
| 15.3 | Streamed responses implemented | In Progress | Widget assistant updates are delivered through the realtime path, but token-level chunk streaming is still to be expanded |
| 15.4 | Widget receives streamed chunks | In Progress | Widget receives assistant updates through the Reverb/WebSocket path; chunk granularity can still be improved |

## 16. Integrations

| ID | Task | Status | Notes |
|---|---|---|---|
| 16.1 | OpenAI env config added | Done | Platform-level OpenAI config exists in `.env`/`services.php`, with per-company override support |
| 16.2 | Vector DB env/config added | Done | Qdrant config exists in `.env`/`services.php`, with per-company override support |
| 16.3 | Service config updated for APIs | Done | Runtime services now resolve effective provider config per company with platform fallback |

## 16A. Proposal Infra

| ID | Task | Status | Notes |
|---|---|---|---|
| 16A.1 | Railway deployment path documented and prepared | Not Started | Proposal requires Railway, current repo has no deployment config |
| 16A.2 | GitHub continuous deployment path documented and prepared | Not Started | Proposal requires automated deployment, current repo has no CD workflow for deploy |
| 16A.3 | WebSocket infrastructure aligned with proposal | Not Started | Proposal requires Reverb/WebSockets, current repo does not implement it |

## 16B. Proposal Compliance Summary

| Area | Proposal Requirement | Current State | Required Action |
|---|---|---|---|
| Local development | Laravel Herd | Not documented | Document Herd as the standard local environment |
| Interactive widget-facing Laravel UI | Livewire 4 | In use | Widget flow uses Livewire while Filament remains unchanged |
| Widget behavior | Alpine.js | In use | Widget interaction layer is Alpine-based |
| Realtime runtime | Laravel Reverb | In use | Reverb is installed and used for widget assistant delivery |
| Streaming transport | WebSockets | In use | Widget assistant delivery uses the WebSocket/Reverb path |
| Deployment | Railway | Not configured | Add Railway deployment strategy/config |
| Deployment automation | GitHub CD | Not configured | Add deployment workflow automation |

## 16C. Proposal Tech Not Yet Used In Code

- `Railway` deployment
- `GitHub Continuous Deployment`

## 17. Reliability

| ID | Task | Status | Notes |
|---|---|---|---|
| 17.1 | OpenAI failures handled safely | In Progress | Chat runtime catches provider errors and falls back safely, but broader retry/logging/observability policy is still missing |
| 17.2 | Vector DB failures handled safely | In Progress | Retrieval and vector upsert paths fall back to local/file-backed behavior when Qdrant is unavailable, but richer resilience and monitoring are still missing |
| 17.3 | Validation/error response format defined | In Progress | Form request validation exists across current APIs, but a unified platform-wide error contract is not documented |
| 17.4 | Logs added for chat, lead, ingestion, fallback | Not Started | Missing |

## 17A. Dashboard Scope Notes

- All dashboard sections must be company-scoped. A company must only see its own leads, chats, knowledge, reports, agent data, and performance data.
- Planned main dashboard sections:
  - Home: reports, summary cards, and client/user tables
  - Leads: tables, statistics, filters, and later export
  - Chat Logs: full chat viewer, transcript download, and later conversation quality status
  - Agent: rename agent, edit starting/welcome message, add future agent features, and show activity logs
  - Profile Settings: company-owned basic profile/business information
  - Knowledge: uploaded knowledge records in a detailed organized table with add/edit flow
  - Dashboard Settings: light/dark mode and basic dashboard preferences
- Dashboard UI should remain in Filament. Proposal-alignment work for Livewire/Alpine/Reverb/WebSockets should be applied to the widget/runtime side, while business logic remains in Services.

## 18. Testing

| ID | Task | Status | Notes |
|---|---|---|---|
| 18.1 | Unit tests for services | Not Started | Missing |
| 18.2 | Feature tests for chat endpoints | Done | Added and passing locally |
| 18.3 | Feature tests for lead flow | Done | Added and passing |
| 18.4 | Feature tests for knowledge upload | Done | Upload and processing coverage added and passing |
| 18.5 | Default example tests replaced or expanded | Done | Feature coverage now includes admin/Filament, agent, chat, lead, and knowledge flows |

## 19. Deployment

| ID | Task | Status | Notes |
|---|---|---|---|
| 19.1 | Railway deployment config prepared | Not Started | Missing |
| 19.2 | Production env checklist prepared | Not Started | Missing |
| 19.3 | Queue/worker plan defined | Not Started | Missing |
| 19.4 | Reverb production strategy defined | Not Started | Missing |

## 20. SaaS Status Snapshot

- Current stage: backend foundation for a SaaS platform, not a production-ready SaaS platform.
- Implemented:
  - Multi-tenant ownership around `agent_id`
  - Authenticated company agent settings APIs
  - Company-scoped chat, lead, and knowledge ingestion APIs
  - OpenAI-backed responses, embeddings, and retrieval with Qdrant or file-backed fallback
  - Working Filament admin panel with custom login, dashboard widgets, and tenant-scoped resources/pages
  - Working iframe widget shell with bootstrap, help, session restore, and API integration
  - General Settings sidebar flow and profile view/edit pages
  - Passing feature tests for admin/Filament, agent, chat, lead, and knowledge flows
- Missing for SaaS readiness:
  - Billing/subscription model
  - Railway deployment and GitHub continuous deployment plan
  - Broader deployment and production operations plan
