# Proyecto de Firmas para Palestina

<img src="http://www.freepalestine.es/favicon.png" alt="Bandera Palestina" width="300"/>

## Descripción

Este proyecto es una plataforma web creada para recolectar firmas en apoyo a la causa palestina. La web permite a los usuarios firmar una petición proporcionando su nombre completo y correo electrónico, y asegura que cada firma sea única y segura.

## Características

- **Formulario de Firma**: Los usuarios pueden ingresar su nombre completo y correo electrónico para firmar la petición.
- **Validación de Firmas**: Se asegura que cada firma sea única y válida.
- **Almacenamiento Seguro**: Los datos de los usuarios se cifran y almacenan de forma segura.
- **Notificaciones por Correo**: Envía notificaciones por correo a los usuarios que firman y al administrador del sitio.
- **Interfaz Intuitiva**: Diseño simple y fácil de usar para garantizar una experiencia de usuario amigable.
- **Soporte Multiplataforma**: Accesible desde dispositivos móviles y de escritorio.

## Tecnologías Utilizadas

- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Base de Datos**: JSON (para almacenamiento de datos)
- **Correo**: EmailJS para envío de correos electrónicos

## Cómo Empezar

1. **Clona el Repositorio**:
    ```sh
    git clone https://github.com/jedahee/FreePalestine.git
    cd FreePalestine
    ```

2. **Configura el Proyecto**:
    - Configura los detalles del servidor y los correos electrónicos en los archivos de configuración.
    - Los archivos de configuración se encuentran en: */config/config.json* y */backend/.env*
3. **Ejecuta el Proyecto**:
    - Sube los archivos a tu servidor web y accede a `index.php` desde tu navegador.

## Contribución

Si deseas contribuir al proyecto, por favor sigue estos pasos:

1. **Fork el Repositorio**
2. **Crea una Rama**: 
    ```sh
    git checkout -b feature-nueva-caracteristica
    ```
3. **Realiza tus Cambios y Haz Commit**: 
    ```sh
    git commit -m "Agregada nueva característica"
    ```
4. **Sube la Rama**: 
    ```sh
    git push origin feature-nueva-caracteristica
    ```
5. **Abre un Pull Request**

## Licencia

Este proyecto está bajo la Licencia MIT. Para más información, consulta el archivo [LICENSE](LICENSE).

## Agradecimientos

Agradecemos a todos los que han apoyado y contribuido a este proyecto. ¡Cada firma cuenta! ✊
