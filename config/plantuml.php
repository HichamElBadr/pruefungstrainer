<?php

return [
    'java_path' => env('PLANTUML_JAVA_PATH') ?: 'java',
    'jar_path' => env('PLANTUML_JAR_PATH') ?: storage_path('app/plantuml/plantuml-1.2025.4.jar'),
    'output_dir' => env('PLANTUML_OUTPUT_DIR', storage_path('app/plantuml/output')),
    'temp_dir' => env('PLANTUML_TEMP_DIR', storage_path('app/plantuml/temp')),
    'java_tmp_dir' => env('PLANTUML_JAVA_TMP_DIR', storage_path('app/plantuml/tmpjava')),
    'retention_seconds' => (int) env('PLANTUML_RETENTION_SECONDS', 3600),
];
