# Optimized Agent Teams with Pre-configured Agents

**Status:** 📋 Planificado
**Priority:** 🟡 Medium
**Estimated Impact:** 💰 50% cost reduction, ⚡ Same performance
**Date Created:** 2026-02-08
**Related Skill:** `.claude/skills/devteam-laravel-skill/`

---

## 📝 Overview

Propuesta para optimizar el sistema DevTeam Laravel mediante la creación de 12 agentes pre-configurados con modelos especializados por fase, manteniendo el paralelismo completo y reduciendo costos operacionales en ~50%.

---

## 🎯 Objetivos

1. **Reducir costos** en 50% usando modelos apropiados por fase
2. **Mantener paralelismo** completo (3 agentes simultáneos por fase)
3. **Mejorar especialización** con herramientas específicas por agente
4. **Configuración reutilizable** entre proyectos
5. **Mantener velocidad** de desarrollo actual

---

## 🏗️ Arquitectura Propuesta

### Current State
```
Lead Agent → Spawn 12 dynamic agents (all Opus 4.6)
├─ Phase 1: Planning (3 agents × Opus 4.6)
├─ Phase 2: Development (3 agents × Opus 4.6)
├─ Phase 3: Testing (3 agents × Opus 4.6)
└─ Phase 4: Documentation (3 agents × Opus 4.6)

Cost: $60/M tokens (assuming uniform usage)
```

### Proposed State
```
Lead Agent → Invoke 12 pre-configured agents (optimized models)
├─ Phase 1: Planning (3 agents × Opus 4.6)      → $15/M tokens
├─ Phase 2: Development (3 agents × Sonnet 4.5) → $9/M tokens
├─ Phase 3: Testing (3 agents × Sonnet 4.5)     → $9/M tokens
└─ Phase 4: Documentation (3 agents × Haiku 4.5) → $3/M tokens

Average Cost: ~$30/M tokens (50% reduction)
```

---

## 🤖 Agent Configuration Specs

### Phase 1: Planning (Opus 4.6 - $5/M)

**1. planning-architect**
```yaml
name: planning-architect
model: claude-opus-4-6
role: Solution Architect
description: Designs system architecture, data models, and technical specifications
tools:
  - Read
  - Write
  - Glob
  - Grep
  - WebSearch
  - Task
context_size: large
thinking_mode: deep
```

**2. planning-business**
```yaml
name: planning-business
model: claude-opus-4-6
role: Business Analyst
description: Documents requirements, user stories, and acceptance criteria
tools:
  - Read
  - Write
  - Glob
  - Grep
  - Task
context_size: large
thinking_mode: balanced
```

**3. planning-security**
```yaml
name: planning-security
model: claude-opus-4-6
role: Security Specialist
description: Defines security requirements, compliance, and data protection
tools:
  - Read
  - Write
  - Grep
  - WebSearch
  - Task
context_size: medium
thinking_mode: balanced
```

---

### Phase 2: Development (Sonnet 4.5 - $3/M)

**4. dev-backend**
```yaml
name: dev-backend
model: claude-sonnet-4-5
role: Backend Engineer
description: Implements Laravel backend code, services, and migrations
tools:
  - Read
  - Write
  - Edit
  - Glob
  - Grep
  - Bash
  - Task
specializations:
  - Laravel 12
  - PHP 8.2+
  - Repository Pattern
  - Service Layer
  - Database Migrations
context_size: large
thinking_mode: balanced
```

**5. dev-frontend**
```yaml
name: dev-frontend
model: claude-sonnet-4-5
role: Frontend Engineer
description: Implements React/Inertia.js frontend and Filament resources
tools:
  - Read
  - Write
  - Edit
  - Glob
  - Grep
  - Bash
  - Task
specializations:
  - React 19
  - Inertia.js v2
  - TypeScript
  - Filament 3
  - Tailwind CSS 4.0
context_size: large
thinking_mode: balanced
```

**6. dev-integration**
```yaml
name: dev-integration
model: claude-sonnet-4-5
role: Integration Specialist
description: Integrates components, APIs, and external services
tools:
  - Read
  - Write
  - Edit
  - Glob
  - Grep
  - Bash
  - Task
specializations:
  - API Integration
  - Wayfinder Routes
  - External Services
  - Data Flow
context_size: medium
thinking_mode: balanced
```

---

### Phase 3: Testing (Sonnet 4.5 - $3/M)

**7. test-unit**
```yaml
name: test-unit
model: claude-sonnet-4-5
role: Unit Test Engineer
description: Creates unit tests for models, services, and utilities
tools:
  - Read
  - Write
  - Edit
  - Bash
  - Task
specializations:
  - Pest PHP 4.1
  - Unit Testing
  - Mocking
  - Datasets
context_size: medium
thinking_mode: focused
```

**8. test-integration**
```yaml
name: test-integration
model: claude-sonnet-4-5
role: Integration Tester
description: Tests API endpoints, controllers, and integrations
tools:
  - Read
  - Write
  - Edit
  - Bash
  - Task
specializations:
  - Pest PHP 4.1
  - Feature Testing
  - API Testing
  - Database Testing
context_size: medium
thinking_mode: focused
```

**9. test-e2e**
```yaml
name: test-e2e
model: claude-sonnet-4-5
role: E2E Test Engineer
description: Creates browser tests and end-to-end workflows
tools:
  - Read
  - Write
  - Edit
  - Bash
  - Task
specializations:
  - Pest PHP 4.1
  - Browser Testing
  - User Flows
  - Visual Testing
context_size: medium
thinking_mode: focused
```

---

### Phase 4: Documentation (Haiku 4.5 - $1/M)

**10. docs-api**
```yaml
name: docs-api
model: claude-haiku-4-5
role: API Documentor
description: Documents API endpoints, request/response formats
tools:
  - Read
  - Write
  - Glob
  - Task
context_size: small
thinking_mode: efficient
```

**11. docs-technical**
```yaml
name: docs-technical
model: claude-haiku-4-5
role: Technical Writer
description: Creates technical documentation and architecture guides
tools:
  - Read
  - Write
  - Glob
  - Task
context_size: small
thinking_mode: efficient
```

**12. docs-user-guide**
```yaml
name: docs-user-guide
model: claude-haiku-4-5
role: User Guide Writer
description: Writes user-facing documentation and guides
tools:
  - Read
  - Write
  - Glob
  - Task
context_size: small
thinking_mode: efficient
```

---

## 📊 Cost-Benefit Analysis

### Token Usage Breakdown (per 1M tokens)

| Phase | Current Cost | Proposed Cost | Savings |
|-------|-------------|---------------|---------|
| Planning (3 agents) | $15 | $15 | 0% (needs Opus) |
| Development (3 agents) | $15 | $9 | 40% |
| Testing (3 agents) | $15 | $9 | 40% |
| Documentation (3 agents) | $15 | $3 | 80% |
| **Total** | **$60** | **$36** | **40%** |

### Project Cost Estimates

| Project Size | Current | Proposed | Savings |
|-------------|---------|----------|---------|
| Small (400K tokens) | $24 | $14 | $10 (42%) |
| Medium (850K tokens) | $51 | $31 | $20 (39%) |
| Large (3M tokens) | $180 | $108 | $72 (40%) |

### Performance Impact

| Metric | Current | Proposed | Change |
|--------|---------|----------|--------|
| Agents per phase | 3 | 3 | ✅ Same |
| Parallelism | Full | Full | ✅ Same |
| Time to complete | T | T | ✅ Same |
| Quality (Planning) | Opus | Opus | ✅ Same |
| Quality (Dev/Test) | Opus | Sonnet | ⚠️ Slight decrease |
| Quality (Docs) | Opus | Haiku | ⚠️ Acceptable |

---

## 🔧 Implementation Plan

### Phase 1: Agent Configuration (2-3 hours)
- [ ] Create 12 agent configuration files
- [ ] Define tool sets per agent
- [ ] Set model assignments
- [ ] Configure thinking modes
- [ ] Test individual agent invocation

### Phase 2: Skill Modification (3-4 hours)
- [ ] Update `SKILL.md` to reference pre-configured agents
- [ ] Modify orchestration logic from `spawn` to `invoke`
- [ ] Update phase definitions with agent names
- [ ] Add model-specific prompting adjustments
- [ ] Update cost estimation formulas

### Phase 3: Testing (4-5 hours)
- [ ] Test Planning phase with 3 Opus agents
- [ ] Test Development phase with 3 Sonnet agents
- [ ] Test Testing phase with 3 Sonnet agents
- [ ] Test Documentation phase with 3 Haiku agents
- [ ] Verify shared task list collaboration
- [ ] Validate checkpoint mechanisms

### Phase 4: Documentation (1-2 hours)
- [ ] Update README.md with new architecture
- [ ] Document model selection rationale
- [ ] Update cost estimates
- [ ] Create migration guide from current system
- [ ] Update troubleshooting guide

### Phase 5: Optimization (2-3 hours)
- [ ] Fine-tune prompts per model capability
- [ ] Adjust tool sets based on usage patterns
- [ ] Optimize context sizes
- [ ] Performance benchmarking
- [ ] Cost tracking implementation

**Total Estimated Time:** 12-17 hours
**Total Estimated Cost (dev time):** ~$200-350 (one-time)
**Break-even Point:** After 3-5 large projects

---

## ⚠️ Risks and Mitigations

### Risk 1: Quality Degradation
**Description:** Sonnet/Haiku may produce lower quality than Opus
**Probability:** Medium
**Impact:** Medium
**Mitigation:**
- Keep Planning phase on Opus (foundation)
- Use Sonnet for code (proven capable)
- Use Haiku only for simple docs
- Add quality validation checkpoints

### Risk 2: Configuration Complexity
**Description:** Managing 12 agent configs is complex
**Probability:** Low
**Impact:** Low
**Mitigation:**
- Use consistent naming convention
- Document each agent thoroughly
- Create config templates
- Version control all configs

### Risk 3: Agent Communication Issues
**Description:** Pre-configured agents may not collaborate well
**Probability:** Low
**Impact:** High
**Mitigation:**
- Test shared task lists extensively
- Verify Agent Teams compatibility
- Maintain peer-to-peer messaging
- Add fallback to dynamic spawn

---

## 📈 Success Metrics

### Primary Metrics
- **Cost Reduction:** Target 40-50% vs current
- **Time to Completion:** Maintain current speed
- **Code Quality:** No degradation in linting/tests
- **User Satisfaction:** Same or better approval rate

### Secondary Metrics
- **Agent Utilization:** Track which agents are most used
- **Model Performance:** Compare Opus vs Sonnet output
- **Error Rates:** Monitor failures by agent type
- **Collaboration Quality:** Shared task list effectiveness

---

## 🔄 Rollback Plan

If the optimized system doesn't meet expectations:

1. **Immediate Rollback** (5 minutes)
   - Revert SKILL.md to current version
   - Continue using dynamic Opus agents
   - Document issues encountered

2. **Partial Rollback** (1 hour)
   - Keep Planning phase optimized
   - Revert other phases to Opus
   - Analyze which phases work well

3. **Hybrid Approach** (2 hours)
   - Use optimized agents for simple projects
   - Use current system for complex projects
   - Implement project-type detection

---

## 📚 References

- **Current Implementation:** `.claude/skills/devteam-laravel-skill/SKILL.md`
- **Architecture Doc:** `.claude/skills/devteam-laravel-skill/references/architecture.md`
- **Cost Optimization:** `.claude/skills/devteam-laravel-skill/references/cost-optimization.md`
- **Workflow Reference:** `.claude/skills/devteam-laravel-skill/references/workflow.md`
- **Agent Teams Docs:** Claude Code experimental features

---

## 🚀 Next Steps

1. **Review this plan** with team/stakeholders
2. **Validate cost assumptions** with real project data
3. **Create POC** with 3-4 agents for one phase
4. **Measure quality** impact before full rollout
5. **Document learnings** for future optimization

---

## 📝 Notes

- Esta propuesta mantiene la arquitectura de 4 fases y 12 agentes
- El paralelismo se conserva completamente (3 agentes simultáneos)
- La colaboración via shared task lists se mantiene
- Los ahorros de costo son estimados y requieren validación
- La implementación es reversible con mínimo esfuerzo

---

**Last Updated:** 2026-02-08
**Next Review:** Cuando se decida implementar
**Owner:** Development Team
