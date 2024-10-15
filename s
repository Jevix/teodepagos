SELECT ms.*, 
                   COALESCE(remitente_entidad.nombre_entidad, remitente.nombre_apellido) AS remitente_nombre,
                   COALESCE(destinatario_entidad.nombre_entidad, destinatario.nombre_apellido) AS destinatario_nombre,
                   remitente_entidad.tipo_entidad AS remitente_tipo_entidad,
                   destinatario_entidad.tipo_entidad AS destinatario_tipo_entidad
            FROM movimientos_saldo ms
            LEFT JOIN entidades AS remitente_entidad ON ms.id_remitente_entidad = remitente_entidad.id_entidad
            LEFT JOIN usuarios AS remitente ON ms.id_remitente_usuario = remitente.id_usuario
            LEFT JOIN entidades AS destinatario_entidad ON ms.id_destinatario_entidad = destinatario_entidad.id_entidad
            LEFT JOIN usuarios AS destinatario ON ms.id_destinatario_usuario = destinatario.id_usuario
            WHERE ms.id_remitente_entidad = 27 OR ms.id_destinatario_entidad = 27
            ORDER BY ms.fecha DESC
            LIMIT 3;