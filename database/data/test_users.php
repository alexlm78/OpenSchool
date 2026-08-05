<?php

return [
    'school' => [
        'name' => 'OpenSchool Demo',
        'email' => 'demo-school@school.test',
        'email_verified_at' => true,
    ],
    'users' => [
        [
            'name' => 'Admin Demo',
            'email' => 'admin@school.test',
            'password' => 'password',
            'role' => 'admin',
        ],
        [
            'name' => 'Docente Demo',
            'email' => 'teacher@school.test',
            'password' => 'password',
            'role' => 'teacher',
            'teacher' => [
                'employee_id' => 'TEACHER-DEMO-001',
                'department' => 'Academico',
                'specialization' => 'Educacion General',
                'phone' => '+56910000001',
            ],
        ],
        [
            'name' => 'Alumno Demo',
            'email' => 'student@school.test',
            'password' => 'password',
            'role' => 'student',
            'student' => [
                'student_id' => 'STUDENT-DEMO-001',
                'date_of_birth' => '2010-03-15',
                'gender' => 'no especificado',
                'address' => 'Direccion Demo 123',
                'phone' => '+56910000002',
            ],
        ],
        [
            'name' => 'Apoderado Demo',
            'email' => 'guardian@school.test',
            'password' => 'password',
            'role' => 'guardian',
            'guardian' => [
                'relationship' => 'apoderado',
                'phone' => '+56910000003',
            ],
            'links' => [
                'students' => [
                    'student@school.test',
                ],
            ],
        ],
        [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ],
    ],
];
