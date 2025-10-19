# EPCT Workflow Command

You are executing the EPCT (Explore → Plan → Code → Test) workflow. Follow these phases strictly and sequentially.

---

## PHASE 1: EXPLORE

**Objective**: Gather ALL necessary context before planning.

### Steps:

1. **Understand the user's request**
   - Clarify ambiguities immediately
   - Identify the scope and requirements
   - Ask critical questions if anything is unclear

2. **Search external resources** (if needed)
   - Use WebSearch for current best practices, libraries, or patterns
   - Research relevant documentation or standards
   - Gather architectural insights

3. **Analyze the codebase**
   - Use the Task tool with subagent_type=Explore for comprehensive codebase exploration
   - Identify related files, patterns, and conventions
   - Understand existing architecture and structure
   - Review similar implementations in the codebase
   - Check configuration files (package.json, composer.json, etc.)
   - Identify testing frameworks and linting tools available

4. **Document findings**
   - Summarize architecture patterns found
   - Note existing conventions (naming, structure, etc.)
   - List relevant files and their purposes
   - Identify dependencies and tools available

**CRITICAL**: Do NOT proceed to Plan phase until you have sufficient context.

---

## PHASE 2: PLAN

**Objective**: Create a detailed, validated plan before writing any code.

### Steps:

1. **Draft comprehensive plan**
   - Break down the implementation into clear steps
   - Identify files to create/modify
   - Specify architectural decisions
   - Consider edge cases and potential issues
   - Outline the approach for each component

2. **Self-challenge your plan**
   - Question your assumptions
   - Identify areas of uncertainty
   - Think critically about alternative approaches
   - Consider scalability, maintainability, and consistency

3. **⚠️ MANDATORY: Stop and ask for validation**
   - Use the AskUserQuestion tool to present your plan
   - Ask specific questions about uncertainties
   - Offer alternatives where multiple approaches exist
   - Challenge yourself: "What could go wrong?" "What am I not sure about?"
   - **DO NOT PROCEED to Code phase without explicit user approval**

**Questions to consider asking:**
- "Should I follow [pattern A] or [pattern B] for this implementation?"
- "I'm uncertain about [X]. Should I [option 1] or [option 2]?"
- "The existing code uses [Y]. Should I maintain consistency or refactor?"
- "What priority should I give to [trade-off 1] vs [trade-off 2]?"

**CRITICAL**: Never assume. Always ask when uncertain. This prevents hallucinations and wrong implementations.

---

## PHASE 3: CODE

**Objective**: Implement the approved plan completely and correctly.

### Steps:

1. **Create todo list**
   - Use TodoWrite to create granular, trackable tasks
   - One task per file or logical component
   - Mark tasks as in_progress, then completed as you work

2. **Implement systematically**
   - Follow the approved plan exactly
   - Maintain consistency with existing codebase patterns
   - Write clean, readable code
   - Add appropriate comments where complexity exists
   - Handle edge cases identified in planning

3. **Track progress**
   - Update todo status in real-time
   - Mark each task completed immediately after finishing
   - Do not batch completions

**CRITICAL**: Only write code that was approved in the Plan phase. If you need to deviate, stop and ask first.

---

## PHASE 4: TEST

**Objective**: Verify the implementation using existing tools only.

### Steps:

1. **Identify available testing/validation tools**
   - Read package.json (for npm scripts)
   - Read composer.json (for PHP scripts)
   - Check for: TypeScript, ESLint, PHPStan, Prettier, test runners
   - Note exact command names from configuration files

2. **Run available checks systematically**
   - Type checking: `npm run type-check`, `tsc --noEmit`, `php artisan test`, etc.
   - Linting: `npm run lint`, `eslint`, `phpstan`, etc.
   - Tests: `npm test`, `php artisan test`, `pytest`, etc.
   - Build: `npm run build`, `composer install`, etc.

3. **Fix issues found**
   - Address type errors, lint errors, test failures
   - Re-run checks after fixes
   - Iterate until all checks pass

4. **Manual verification**
   - If no automated tests exist, provide manual testing steps
   - Suggest commands to verify the feature works (e.g., curl, docker commands)

**CRITICAL RULES**:
- ❌ Do NOT create new tests or test commands that don't exist
- ❌ Do NOT run commands that aren't in configuration files
- ❌ Do NOT assume testing tools are available
- ✅ ONLY use commands you found in Step 1
- ✅ If no tests exist, suggest manual verification steps instead

---

## WORKFLOW RULES

1. **Sequential execution**: Complete each phase before starting the next
2. **No skipping**: Every phase is mandatory
3. **User checkpoint**: Always stop at Plan phase for validation
4. **Stay grounded**: Only use tools and commands that exist
5. **Track everything**: Use TodoWrite throughout Code phase
6. **Question assumptions**: When uncertain, ask—never hallucinate

---

## EXAMPLE EXECUTION

**User**: "Add a feature to export budgets to CSV"

**Phase 1 - Explore**:
- Explores codebase for export features, CSV libraries, budget models
- Checks package.json for available dependencies
- Identifies similar export features in the codebase

**Phase 2 - Plan**:
- Drafts plan: "Create BudgetExportService, add CSV library, create API endpoint, add frontend button"
- Asks: "Should I use fast-csv or csv-writer library?"
- Asks: "Should the export include transaction details or just summaries?"
- **WAITS for user approval**

**Phase 3 - Code**:
- Creates todo: Install library, create service, add endpoint, add button, wire up
- Implements each item, marking completed as done

**Phase 4 - Test**:
- Reads package.json, finds: `npm run type-check`, `npm test`, `npm run lint`
- Runs each command, fixes issues
- Suggests: "Test manually by clicking Export button and verifying CSV contents"

---

**BEGIN EPCT WORKFLOW NOW**
