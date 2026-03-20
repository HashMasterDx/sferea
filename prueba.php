<?php

class ExcepcionTicketTI extends Exception {}

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

abstract class GestorBaseTickets
{
    protected $notificador;

    public function __construct(Notificacion $notificador)
    {
        $this->notificador = $notificador;
    }

    protected function registrarEvento(string $mensaje)
    {
        echo "[BITACORA] {$mensaje}" . PHP_EOL;
    }

    public function levantarTicket(string $solicitante, string $descripcion, string $prioridad): array
    {
        if ($descripcion === '') {
            throw new ExcepcionTicketTI('La descripcion del ticket es obligatoria.');
        }

        if (!in_array($prioridad, ['baja', 'media', 'alta'], true)) {
            throw new ExcepcionTicketTI('Prioridad invalida. Use baja, media o alta.');
        }

        $ticket = [
            'id' => random_int(1000, 9999),
            'solicitante' => $solicitante,
            'descripcion' => $descripcion,
            'prioridad' => $prioridad,
            'estado' => 'abierto',
        ];

        $this->registrarEvento("Ticket #{$ticket['id']} levantado por {$solicitante}");

        return $this->resolverTicket($ticket);
    }

    abstract protected function resolverTicket(array $ticket): array;
}

class GestorTicketsNivel1 extends GestorBaseTickets
{
    protected function resolverTicket(array $ticket): array
    {
        $this->registrarEvento("Analizando ticket #{$ticket['id']} en Nivel 1");

        if ($ticket['prioridad'] === 'alta') {
            $ticket['estado'] = 'escalado a nivel 2';
            parent::registrarEvento("Ticket #{$ticket['id']} escalado por alta prioridad");
            $this->notificador->enviar(
                $ticket['solicitante'],
                "Tu ticket #{$ticket['id']} fue escalado a soporte especializado."
            );

            return $ticket;
        }

        $ticket['estado'] = 'resuelto en nivel 1';
        $this->notificador->enviar(
            $ticket['solicitante'],
            "Tu ticket #{$ticket['id']} fue resuelto por soporte de primer nivel."
        );

        return $ticket;
    }
}

class ControladorSoporte
{
    private $gestor;

    public function __construct(GestorTicketsNivel1 $gestor)
    {
        $this->gestor = $gestor;
    }

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
}

$controlador = new ControladorSoporte(
    new GestorTicketsNivel1(new NotificadorCorreo())
);

// Caso existoso
print_r($controlador->crearTicket('ana@empresa.com', 'No puedo ingresar al VPN', 'media'));

// Caso con error de descripcion vacia
print_r($controlador->crearTicket('carlos@empresa.com', '', 'alta'));
