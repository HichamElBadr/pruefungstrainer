# Prüfungstrainer – AI-Driven Learning Platform for Technical Education

Prüfungstrainer is a modular, AI-powered learning platform designed for **technical education and IT professions**.

It combines a Laravel-based web application with a dedicated Python-based AI Gateway to generate structured, robust, and reproducible learning tasks using locally hosted language models.

The system is designed for extensibility, privacy, and long-term maintainability.

---

## Core Philosophy

- Privacy-first (fully local AI via Ollama)
- Clear architectural separation (Web ↔ AI Gateway)
- Deterministic, structured AI output (validated JSON)
- Extensible task architecture
- Designed for real educational environments

This is not just a prototype — it is structured as a long-term platform.

---

## Key Features

- AI-generated tasks (SQL, UML, calculation tasks, extensible)
- In-browser task execution
- Isolated SQL practice environment with temporary databases
- UML diagram rendering via PlantUML
- Robust AI output validation layer (JSON normalization)
- Python-based AI microservice (`ai-gateway/`)
- Modular category-based task system
- Designed for multi-model support and model routing

---

## System Architecture

The system is split into two independent layers:

1. Laravel Application  
   - Web UI  
   - Business logic  
   - Database management  
   - Task rendering  

2. AI Gateway (Python Service)  
   - Prompt orchestration  
   - Model communication (Ollama)  
   - Output validation & normalization  
   - Schema enforcement  
   - Future: model routing & response caching  

This separation ensures:

- Clean responsibility boundaries
- Independent scaling
- Easier model replacement
- Cleaner testing

---

## High-Level Data Flow

1. User selects a task type (e.g. SQL)
2. Laravel sends a structured request to the AI Gateway
3. AI Gateway builds a controlled prompt
4. Ollama generates raw output
5. AI Gateway validates & normalizes JSON
6. Laravel renders task + solution
7. User executes solution (if applicable)

---

## Example Structured AI Response

Example normalized response from AI Gateway:

{
  "task": "Write a SQL query to retrieve all customers with orders above 1000€.",
  "mysqlstatement": "CREATE TABLE customers (...); INSERT INTO ...;",
  "solution": "SELECT ... FROM customers JOIN orders ...;",
}

All responses are validated before being forwarded to the web application.

---

## Technology Stack

### Web Layer
- Laravel 12
- PHP 8.2+
- MySQL
- Blade

### AI Layer
- Python (FastAPI recommended)
- Ollama (local LLM runtime)
- Structured JSON validation
- Schema enforcement

### Diagram Rendering
- PlantUML (local JAR execution)

---

## Repository Structure

- app/              Laravel application code
- routes/           HTTP routes
- database/         Migrations and seeders
- docs/             Technical documentation
- ai-gateway/       Python AI microservice
- tests/            Automated tests

---

## Screenshots

### Login Interface

![login](image.png)

### SQL Practice Interface

<!-- SCREENSHOT: SQL task page with editor and result table -->

### UML Generation

<!-- SCREENSHOT: UML text input and generated diagram -->

---

## Quickstart (Conceptual)

This project requires two running services:

1. Laravel Web Application
2. AI Gateway (Python)

Additionally required:
- Local Ollama installation
- MySQL
- Java (for PlantUML)

Detailed setup instructions should be documented in:
docs/INSTALLATION.md

---

## Security Considerations

- Fully local AI execution (no external API calls)
- SQL environment should restrict destructive queries
- Environment variables used for configuration
- AI responses validated before use
- Errors handled gracefully

---

## Extensibility Strategy

The system is designed to evolve in the following directions:

- Additional task types (networking, programming, security)
- Difficulty-based generation
- Model routing (task model vs solution model)
- Multi-model benchmarking
- RAG integration
- Learning state persistence
- Caching layer in AI Gateway
- Horizontal deployment

---

## Development Philosophy

- Clear separation of concerns
- Minimal coupling between AI and UI
- Service-oriented architecture
- Feature branches
- Controlled AI outputs
- Future-ready for distributed deployment

---

## Roadmap

- Model routing architecture
- Response caching
- Structured prompt versioning
- Performance monitoring
- Docker-based deployment
- Automated tests for AI Gateway
- Role-based permission system

---

## Contribution Guidelines

- Use feature branches
- No secrets in commits
- Keep PRs focused and reviewable
- Add documentation for new modules
- Keep AI responses schema-compliant

---

## License

This project is licensed under the GNU General Public License v3.0.

---

## Author

Hicham El Badr
