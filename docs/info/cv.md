# Módulo: Currículum Vitae (CV)

Módulo para gestionar un currículum vitae completo con 16 secciones organizadas en experiencia laboral (5 tipos), formación académica (3 tipos), habilidades, proyectos, repositorios, servicios, colaboraciones, hobbies y trabajos. Incluye generación de PDF con DomPDF.

## Archivos principales

### Modelos
| Archivo | Tabla | Descripción |
|---------|-------|-------------|
| `app/Models/CV/Curriculum.php` | `cv` | CV principal (perfil) |
| `app/Models/CV/CurriculumBaseSection.php` | — | Modelo base abstracto para secciones |
| `app/Models/CV/CurriculumExperienceAccredited.php` | `cv_experience_accredited` | Experiencia acreditada |
| `app/Models/CV/CurriculumExperienceNoAccredited.php` | `cv_experience_no_accredited` | Experiencia no acreditada |
| `app/Models/CV/CurriculumExperienceSelfEmployed.php` | `cv_experience_self_employed` | Experiencia autónomo |
| `app/Models/CV/CurriculumExperienceAdditional.php` | `cv_experience_additional` | Experiencia adicional |
| `app/Models/CV/CurriculumExperienceOther.php` | `cv_experience_others` | Otra experiencia |
| `app/Models/CV/CurriculumAcademicTraining.php` | `cv_academic_training` | Formación reglada |
| `app/Models/CV/CurriculumAcademicComplementary.php` | `cv_academic_complementary` | Formación complementaria |
| `app/Models/CV/CurriculumAcademicComplementaryOnline.php` | `cv_academic_complementary_online` | Formación online |
| `app/Models/CV/CurriculumSkill.php` | `cv_skills` | Habilidades |
| `app/Models/CV/CurriculumProject.php` | `cv_projects` | Proyectos |
| `app/Models/CV/CurriculumRepository.php` | `cv_repositories` | Repositorios |
| `app/Models/CV/CurriculumService.php` | `cv_services` | Servicios ofrecidos |
| `app/Models/CV/CurriculumCollaboration.php` | `cv_collaborations` | Colaboraciones |
| `app/Models/CV/CurriculumHobby.php` | `cv_hobbies` | Hobbies |
| `app/Models/CV/CurriculumJob.php` | `cv_jobs` | Trabajos |
| `app/Models/CV/CurriculumAvailableRepositoryType.php` | `cv_available_repository_types` | Tipos de repositorio |

### Controladores
| Archivo | Versión | Descripción |
|---------|---------|-------------|
| `app/Http/Controllers/Api/Cv/V2/CvController.php` | API V2 | 10 endpoints públicos de lectura |
| `app/Http/Controllers/Cv/CurriculumController.php` | Web | Frontend público + generación PDF |
| `app/Http/Controllers/Dashboard/Cv/*.php` | Dashboard | 17 controladores admin legacy |
| `app/Http/Controllers/Dashboard/CurriculumController.php` | Dashboard | CV general admin |

### Servicios
| Archivo | Descripción |
|---------|-------------|
| `app/Services/Cv/CurriculumService.php` | `getFullCurriculum(User)` — carga CV completo con 16 relaciones |

### Enums
| Archivo | Descripción |
|---------|-------------|
| `app/Enums/CvRepositoryTypeEnum.php` | Tipos de repositorio (GitHub, GitLab, etc.) |

### Otros
| Archivo | Descripción |
|---------|-------------|
| `app/Policies/CurriculumPolicy.php` | Política de autorización |

## Relaciones del modelo Curriculum (cargadas en getFullCurriculum)

| Relación | Modelo destino | Tipo |
|----------|---------------|------|
| `repositories` | `CurriculumRepository` | HasMany |
| `services` | `CurriculumService` | HasMany |
| `collaborations` | `CurriculumCollaboration` | HasMany |
| `hobbies` | `CurriculumHobby` | HasMany |
| `jobs` | `CurriculumJob` | HasMany |
| `projects` | `CurriculumProject` | HasMany |
| `academicTraining` | `CurriculumAcademicTraining` | HasMany |
| `academicComplementary` | `CurriculumAcademicComplementary` | HasMany |
| `academicComplementaryOnline` | `CurriculumAcademicComplementaryOnline` | HasMany |
| `experienceAccredited` | `CurriculumExperienceAccredited` | HasMany |
| `experienceNoAccredited` | `CurriculumExperienceNoAccredited` | HasMany |
| `experienceSelfEmployed` | `CurriculumExperienceSelfEmployed` | HasMany |
| `experienceAdditional` | `CurriculumExperienceAdditional` | HasMany |
| `experienceOther` | `CurriculumExperienceOther` | HasMany |
| `skills` | `CurriculumSkill` | HasMany |

## Rutas API V2

| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| GET | `/api/v2/cv` | No | CV completo |
| GET | `/api/v2/cv/experience` | No | Experiencia laboral (5 tipos) |
| GET | `/api/v2/cv/education` | No | Formación (3 tipos) |
| GET | `/api/v2/cv/skills` | No | Habilidades |
| GET | `/api/v2/cv/projects` | No | Proyectos |
| GET | `/api/v2/cv/repositories` | No | Repositorios |
| GET | `/api/v2/cv/services` | No | Servicios |
| GET | `/api/v2/cv/collaborations` | No | Colaboraciones |
| GET | `/api/v2/cv/hobbies` | No | Hobbies |
| GET | `/api/v2/cv/jobs` | No | Trabajos |

## Rutas Web

| Ruta | Descripción |
|------|-------------|
| `/cv/get/pdf/raupulus/default` | Generar y descargar PDF del CV |

## Notas

- El endpoint `/api/v2/cv` devuelve el CV del usuario con `role_id=1` (SuperAdmin)
- La generación de PDF usa DomPDF
- Todas las secciones del CV se comparten mediante `CurriculumBaseSection` como modelo base
