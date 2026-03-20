# Lennyn Leyva Andrade - 19-03-2024

## Evaluacion de Conocimientos - Metodologia Fast Track

Este documento detalla cómo cada concepto solicitado está implementado en el código de `prueba.php` con un flujo real de levantamiento de ticket de TI.

---

## 1) Constructor

**Definicion:** Método especial que se ejecuta al crear una instancia de la clase. Se utiliza para inicializar propiedades y inyectar dependencias.

**Implementacion en el codigo:**

```php
abstract class GestorBaseTickets
{
    protected $notificador;

    public function __construct(CanalNotificacion $notificador)
    {
        $this->notificador = $notificador;
    }
}
```

**Implementación:** 
- El constructor `__construct` se ejecuta al crear instancias de `GestorBaseTickets` y sus hijas.
- Recibe `CanalNotificacion $notificador` como dependencia (inyección de dependencias tipo Laravel).
- Inicializa la propiedad `$notificador` para ser usada en todo el flujo.

**Caso de uso en la prueba:**

Al instanciar `GestorTicketsNivel1`, se ejecuta el constructor que recibe `NotificadorCorreo`.

---

## 2) Método

**Definicion:** Función que representa un comportamiento u operación de una o más reglas de negocio.

**Implementacion en el codigo:**

Métodos públicos:
```php
public function levantarTicket(string $solicitante, string $descripcion, string $prioridad): array
{
    // lógica de validación y creación
    return $this->resolverTicket($ticket);
}

public function crearTicket(string $solicitante, string $descripcion, string $prioridad): array
{
    // manejo de excepciones
}
```

Métodos protegidos:
```php
protected function registrarEvento(string $mensaje)
{
    echo "[BITACORA] {$mensaje}" . PHP_EOL;
}

protected function resolverTicket(array $ticket): array
{
    // implementación en clases hijas
}
```

**Implementación:**
- 4 métodos públicos y protegidos en las clases.
- Cada método tiene responsabilidad clara (crear ticket, registrar evento, resolver).
- Usan tipos de datos en parámetros y retorno (`string`, `array`).

---

## 3) Herencia

**Definicion:** Permite que una clase hija reutilice y extienda el comportamiento de una clase padre usando `extends`.

**Implementacion en el codigo:**

```php
class GestorTicketsNivel1 extends GestorBaseTickets
{
    protected function resolverTicket(array $ticket): array
    {
        $this->registrarEvento("Analizando ticket #{$ticket['id']} en Nivel 1");

        if ($ticket['prioridad'] === 'alta') {
            $ticket['estado'] = 'escalado a nivel 2';
            parent::registrarEvento("Ticket #{$ticket['id']} escalado por alta prioridad");
            // ...
        }
        // ...
    }
}
```

**Implementación:**
- `GestorTicketsNivel1` extiende `GestorBaseTickets` (herencia explícita).
-  Reutiliza métodos de la padre: `registrarEvento()`, constructor, `levantarTicket()`.
-  Implementa el método abstracto `resolverTicket()` de forma específica.
-  Accede a métodos heredados con `$this->registrarEvento()`.

---

## 4) Excepcion ✓

**Definicion:** Mecanismo para manejar errores de forma controlada.

**Implementacion en el codigo:**

Definición de excepción personalizada:
```php
class ExcepcionTicketTI extends Exception {}
```

Lanzamiento de excepciones:
```php
public function levantarTicket(string $solicitante, string $descripcion, string $prioridad): array
{
    if ($descripcion === '') {
        throw new ExcepcionTicketTI('La descripcion del ticket es obligatoria.');
    }

    if (!in_array($prioridad, ['baja', 'media', 'alta'], true)) {
        throw new ExcepcionTicketTI('Prioridad invalida. Use baja, media o alta.');
    }
    // ...
}
```

Captura de excepciones:
```php
public function crearTicket(string $solicitante, string $descripcion, string $prioridad): array
{
    try {
        return [
            'ok' => true,
            'ticket' => $this->gestor->levantarTicket($solicitante, $descripcion, $prioridad),
        ];
    } catch (ExcepcionTicketTI $e) {
        return [
            'ok' => false,
            'error' => $e->getMessage(),
        ];
    }
}
```

**Implementación:**
- Excepción personalizada `ExcepcionTicketTI` que extiende `Exception`.
- Se lanza con `throw` en validaciones de negocio (descripción vacía, prioridad inválida).
- Se captura con `try/catch` en el controlador.
- Manejo elegante de errores: retorna respuesta con `['ok' => false]` en lugar de fallar.

**Caso de prueba:**
```
Entrada: 'carlos@empresa.com', '', 'alta'
Salida:  ['ok' => false, 'error' => 'La descripcion del ticket es obligatoria.']
```

---

## 5) Interfaz

**Definicion:** Es como un contrato o un plano de construcción que define qué métodos debe tener una clase, pero sin decir cómo deben funcionar internamente. Se usa `implements`.

**Implementacion en el codigo:**

```php
interface Notificacion
{
    public function enviar(string $destinatario, string $mensaje);
}

class NotificadorCorreo implements Notificacion
{
    public function enviar(string $destinatario, string $mensaje)
    {
        echo "[Correo a {$destinatario}] {$mensaje}" . PHP_EOL;
    }
}
```

**Implementación:**
- `Notificacion` define el contrato con el método `enviar()`.
- `NotificadorCorreo` implementa la interfaz con `implements`.
-  Obligatoriamente implementa el método `enviar()` según la firma del contrato.
-  Permite polimorfismo: cualquier `Notificacion` puede inyectarse en el constructor.

**Uso en la clase abstracta:**
```php
public function __construct(CanalNotificacion $notificador)
{
    $this->notificador = $notificador;
}
```
Esto permite que diferentes implementaciones (Correo, Slack, SMS, etc.) cumplan el mismo contrato.

---

## 6) Clase Abstracta

**Definicion:** Clase que no se puede instanciar directamente. Define métodos concretos, abstractos y actúa como base común para sus hijas.

**Implementacion en el codigo:**

```php
abstract class GestorBaseTickets
{
    protected $notificador;

    public function __construct(CanalNotificacion $notificador)
    {
        $this->notificador = $notificador;
    }

    protected function registrarEvento(string $mensaje)
    {
        echo "[BITACORA] {$mensaje}" . PHP_EOL;
    }

    public function levantarTicket(string $solicitante, string $descripcion, string $prioridad): array
    {
        // implementación concreta compartida
        return $this->resolverTicket($ticket);
    }

    abstract protected function resolverTicket(array $ticket): array;
}
```

**Implementación:**
- Tiene métodos concretos reutilizables: constructor, `registrarEvento()`, `levantarTicket()`.
- Tiene método abstracto: `resolverTicket()` que obliga a las hijas a implementarlo.
- No se puede instanciar directamente.
- `GestorTicketsNivel1` extiende y implementa el método abstracto.

**Caso de uso:**
```php
// Esto es ilegal:
new GestorBaseTickets(...); // Error: cannot instantiate abstract class

// Esto es correcto:
new GestorTicketsNivel1(new NotificadorCorreo()); // OK
```

---

## 7) Diferencia entre This y Super ✓

**Definicion:** 
- `$this` hace referencia al objeto actual.
- `parent::` es el equivalente de `super` en PHP, accede a métodos/propiedades de la clase padre.

**Implementacion en el codigo:**

Uso de `$this` (objeto actual):
```php
class GestorTicketsNivel1 extends GestorBaseTickets
{
    protected function resolverTicket(array $ticket): array
    {
        // $this apunta a la instancia actual de GestorTicketsNivel1
        $this->registrarEvento("Analizando ticket #{$ticket['id']} en Nivel 1");

        if ($ticket['prioridad'] === 'alta') {
            $ticket['estado'] = 'escalado a nivel 2';
            // Accedemos a propiedad heredada
            $this->notificador->enviar(...);
        }
    }
}
```

Uso de `parent::` (clase padre):
```php
protected function resolverTicket(array $ticket): array
{
    if ($ticket['prioridad'] === 'alta') {
        $ticket['estado'] = 'escalado a nivel 2';
        // parent:: llama al método de la clase padre GestorBaseTickets
        parent::registrarEvento("Ticket #{$ticket['id']} escalado por alta prioridad");
    }
}
```

**Implementación:**
- $this->registrarEvento()` invoca el método de la clase actual (hereda de padre si existe).
- `$this->notificador` accede a la propiedad definida en el constructor de la clase padre.
- `parent::registrarEvento()` llama explícitamente a la implementación del padre.
- Ambos están en el contexto de herencia (`GestorTicketsNivel1 extends GestorBaseTickets`).

---
