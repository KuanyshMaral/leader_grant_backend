<?php
class ClientAgentLink {
    private int $id;

    // Many-to-One: У Агента много связей
    private User $agent_user;      // (ForeignKey -> users.id, role='agent')

    // Many-to-One: У Клиента много связей (хотя в этой паре одна)
    private User $client_user;     // (ForeignKey -> users.id, role='client')

    private string $status;        // (Enum: 'pending_invite', 'linked')
}
?>