<?php foreach($EM_Booking->get_tickets() as $EM_Ticket): ?>
<?php echo $EM_Ticket->name; ?>

Quantity: <?php echo $EM_Ticket->spaces; ?>

Price: <?php echo em_get_currency_symbol(true)." ". number_format($EM_Ticket->get_price(true),2); ?>
<?php endforeach; ?>