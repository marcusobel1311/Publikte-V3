# Publicate
Version 0.10.37

-Cualquier cambio o modificacion por favor informar
-El dia 4/07/2025 se implementara otra actualizacion por parte de nosotros.

El mensaje **"Vendedor no especificado."** aparece cuando la URL no incluye el parámetro `id` o el valor es incorrecto (vacío, cero o no numérico).

### ¿Cómo debes acceder?
Debes poner la URL así, con el parámetro `id` y el número real del vendedor:
```
http://localhost/publikte/PUBLICATE-MARKETPLACE/profile/seller/public.php?id=1
```
Cambia el `1` por el ID real del vendedor que quieres ver.

---

### ¿Cómo saber el ID del vendedor?
- Si entras a un producto, el enlace al perfil del vendedor debería verse así:
  ```
  profile/seller/index.php?id=5
  ```
  El número después de `id=` es el que debes usar.

- Si no sabes el ID, dime el nombre de usuario o el producto y te ayudo a encontrarlo.

---

### ¿Quieres que los enlaces desde los productos lleven a este perfil público?
Puedo cambiar los enlaces para que apunten a `public.php` en vez de `index.php` si lo prefieres.

---

**Resumiendo:**  
- Si ves "Vendedor no especificado." es porque falta el parámetro `id` en la URL.
- Si pones la URL con el `id` correcto, verás los productos y reseñas.

¿Quieres que cambie los enlaces para que siempre lleven a este perfil público? ¿O necesitas ayuda para encontrar el ID de un vendedor?
