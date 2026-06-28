# Documento de Diseño Arquitectural y Funcional de OpenSchool

## Visión General

OpenSchool es una plataforma de gestión educativa integral construida sobre Laravel 13.x, diseñada para manejar múltiples instituciones educativas (multitenancy) con roles diferenciados (administradores, docentes, alumnos, apoderados) y funcionalidades que cubren desde la matrícula hasta la evaluación y seguimiento académico.

## Arquitectura Técnica

### Stack Tecnológico
- **Backend**: Laravel 13.x (PHP framework)
- **Paneles de Administración**: Filament v5.6 (admin panels)
- **Componentes Reactivos**: Livewire v4.3 (interfaz de usuario dinámica)
- **Gestión de Permisos**: Spatie Laravel Permissions
- **Autenticación**: Laravel Sanctum (para API móvil)
- **Búsqueda**: Laravel Scout con Meilisearch driver
- **Colas y Jobs**: Redis (driver de cola)
- **Almacenamiento de Archivos**: Disco privado (local/S3 configurables)
- **Logging**: Laravel Pail/Pao con correlation ID
- **Asset Bundling**: Vite
- **Testing**: Pest/PHPUnit, Laravel Pint (estilo de código)
- **Base de Datos**: SQLite (desarrollo), configurable a MySQL/PostgreSQL

### Patrón de Multitenancy
- Implementación mediante **global scope** en todos los modelos relevantes
- Cada tabla principal incluye columna `school_id` (except tablas de sistema como users, passwords_resets, etc.)
- Las políticas de Spatie Permissions usan `team_id` = `school_id` para aislar roles y permisos por institución
- Middleware de tenancy que establece el `school_id` actual basado en el subdominio o tokens de API

### Estructura de Módulos

#### 1. Módulo de Tenancy y Usuarios
- **School**: representa una institución educativa
- **User**: sistema de autenticación centralizado (pero aislado por school_id vía scope)
- **Role/Permission**: gestionados por Spatie, aislados por school
- Modelos relacionados: Student, Teacher, Guardian, GuardianStudent (pivot)

#### 2. Modelo Académico
- **AcademicPeriod**: períodos lectivos (trimestres, semestres, etc.)
- **CourseTemplate**: definición abstracta de un curso (nombre, código, créditos)
- **CourseOffering**: instancia específica de un CourseTemplate en un AcademicPeriod con capacidad y horarios
- **TimeSlot**: bloques de tiempo horarios (lunes 8-10am, etc.)
- **OfferingTimeSlot**: asignación de horarios a un CourseOffering
- **TeachingAssignment**: asignación de un Teacher a un CourseOffering (una sección)
- **Enrollment**: matrícula de un Student en un CourseOffering (con validación de conflictos de horario y capacidad)

#### 3. Evaluaciones y Entregas
- **Evaluation**: evaluación polimórfica (puede ser tarea, examen, proyecto, etc.)
  - Tipos mediante relaciones: AssignmentDetails, ExamDetails, ProjectDetails
- **Submission**: entrega de un estudiante para una Evaluation
- **SubmissionFile**: archivos adjuntos a una Submission (almacenamiento privado)
- **Grade**: calificación y feedback asociada a una Submission
- **Observers/Events**: generan notificaciones (coladas) cuando se crean/actualizan evaluations, submissions, grades

#### 4. Interfaces de Usuario
- **Admin Panel** (Filament): gestión completa de escuelas, usuarios, roles, períodos, plantillas de cursos, ofertaciones, asignaciones, matrículas
- **Docente Panel** (Filament): vista limitada a las secciones asignadas al docente; permite crear evaluaciones, ver entregas, calificar
- **Alumno Portal** (Livewire): 
  - Ver cursos inscritos y su horario
  - Ver evaluaciones pendientes y calificadas
  - Subentar entregas y ver feedback
  - Calendario académico
- **Apoderado Portal** (Livewire):
  - Vinculación con uno o más estudiantes
  - Ver calendario consolidado de evaluaciones de sus hijos
  - Ver calificaciones y progreso por período
  - Recibir notificaciones
- **API Móvil** (Sanctum):
  - Endpoints autenticados para cursos, evaluaciones, submissions, grades
  - Misma capa de políticas y tenancy que las interfaces web

## Flujo de Trabajo por Tipo de Usuario

### Administrador
1. Inicia sesión en /admin
2. Gestiona escuelas (crear/editar/desactivar)
3. Crea usuarios y asigna roles (admin, docente, etc.) dentro de su escuela
4. Define períodos académicos
5. Gestiona catálogo de plantillas de cursos
6. Aprueba ofertaciones de cursos (creadas por coordinadores o directamente)
7. Supervisa matrículas y reportes

### Docente
1. Inicia sesión en /docente
2. Ve su panel con las secciones asignadas (CourseOfferings donde es teacher)
3. Para cada sección:
   - Ve lista de estudiantes inscritos
   - Crea evaluaciones (tareas, exámenes, proyectos) con fechas y descripción
   - Publica evaluaciones (visibles para alumnos)
   - Revisa entregas subidas por estudiantes
   - Calienta entregas y proporciona feedback
   - Publica calificaciones
4. Puede ver reportes de su sección

### Alumno
1. Inicia sesión en /alumno
2. Ve su tablero con:
   - Cursos inscritos del período actual
   - Próximas evaluaciones
   - Calendario académico
3. Accede a un curso específico para:
   - Ver detalle de evaluaciones
   - Subentar entregas (con archivos adjuntos)
   - Ver calificaciones y feedback de entregas ya calificadas
   - Participar en actividades (según configuración)

### Apoderado
1. Inicia sesión en /apoderado
2. Vincula a sus hijos (si aún no lo está) mediante código de estudiante o solicitud aprobada por admin
3. Ve resumen de todos sus hijos vinculados:
   - Próximas evaluaciones de cada hijo
   - Calendario combinado
   - Promedios y progreso por período
4. Accede al detalle de un hijo para ver su desempeño curso por curso

## Consideraciones para Pruebas de Usuario

### Escenarios de Prueba Clave

#### Flujo de Matrícula y Horario
1. Admin crea un período académico
2. Admin crea una plantilla de curso (ej: "Matemáticas 101")
3. Admin crea una oferta de ese curso en el período (capacity: 30)
4. Admin crea bloques horarios (TimeSlots) y los asigna a la oferta
5. Admin crea un docente y le asigna la oferta (TeachingAssignment)
6. Admin crea un estudiante
7. Estudiante se matricula en la oferta (Enrollment) - debe validar capacidad y conflictos
8. Estudiante intenta matricularse en otra oferta con horario conflictivo -> debe mostrar error
9. Estudiante visualiza su horario en el portal alumno

#### Flujo de Evaluación y Calificación
1. Docente crea una evaluación (tipo tarea) en su sección asignada
2. Docente establece fecha de entrega y adjunta rúbrica (opcional)
3. Alumno ve la evaluación en su portal
4. Alumno sube una entrega antes de la fecha límite
5. Docente recibe notificación (por cola) y revisa la entrega
6. Docente califica la entrega y deja feedback
7. Alumno ve la calificación y feedback en su portal
8. Apoderado vinculado ve la calificación en su portal consolidado

#### Flujo de Notificaciones
1. Al crear una evaluación, se dispara un event que envía notificación a alumnos inscritos (por cola)
2. Al calificar una entrega, se dispara notificación al alumno y apoderado
3. Verificar que lasnotificaciones llegan correctamente (pueden inspeccionarse en la tabla de notificaciones o logs)

#### Pruebas de Multitenancy
1. Crear dos escuelas distintas (Escuela A y Escuela B)
2. Crear usuarios, cursos, evaluaciones en cada escuela
3. Iniciar sesión como usuario de Escuela A y verificar que no ve datos de Escuela B
4. Repetir para Escuela B
5. Verificar que los roles y permisos estan aislados (un docente de A no puede gestionar secciones de B)

#### Pruebas de API Móvil
1. Autenticar vía Sanctum (token)
2. Acceder a endpoints de cursos, evaluaciones, submissions
3. Verificar que las respuestas respetan tenancy y políticas
4. Intentar acceder a recursos de otra escuela -> debe retornar 403/404

### Datos de Prueba Recomendados
- **Escuelas**: 2-3 instituciones con diferentes configuraciones
- **Usuarios por escuela**: 
  - 1 admin
  - 2-3 docentes
  - 5-10 estudiantes
  - 2-3 apoderados (vinculados a estudiantes)
- **Períodos académicos**: 1-2 activos, alguno histórico
- **Cursos**: 5-10 plantillas, 2-3 ofertaciones por período
- **Evaluaciones**: 2-3 por curso/ofrertación
- **Entregas**: al menos una por estudiante por evaluación (con variaciones: a tiempo, tardía, sin entregar)

### Herramientas de Prueba
- **Navegadores**: Chrome/Firefox para probar interfaces web
- **Herramientas API**: Postman o Insomnia para probar endpoints API
- **Base de Datos**: inspeccionar directamente para validar aislazión y integridad
- **Colas**: usar `php artisan queue:work` en desarrollo para verificar disparo de jobs
- **Logs**: revisar `storage/logs/` para depuración

## Próximos Pasos en el Desarrollo (Según PLAN.md)

Tras completar las migraciones y modelos (fases 1-4), el siguiente paso es construir los paneles Filament (fase 5):
1. Admin Panel: gestionar escuelas, usuarios, roles, períodos, plantillas, ofertaciones, matrículas
2. Docente Panel: gestionar secciones asignadas, crear evaluaciones, calificar entregas

Posteriormente:
- Fase 6: Portales Livewire para alumno y apoderado
- Fase 7: API móvil con Sanctum
- Fase 8: Infraestructura (Redis, Scout, privés disks, logging)
- Fase 9: Testing y calidad
- Fase 10: Despliegue y operaciones

Este documento sirve como referencia para realizar pruebas de usuario válidas que cubran los flujos críticos del sistema.