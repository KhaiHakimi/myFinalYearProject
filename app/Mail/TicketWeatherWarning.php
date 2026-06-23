<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

use App\Models\Booking;
use App\Models\Schedule;

class TicketWeatherWarning extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;
    public $schedule;

    /**
     * Create a new message instance.
     */
    public function __construct(Booking $booking, Schedule $schedule)
    {
        $this->booking = $booking;
        $this->schedule = $schedule;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Weather Advisory: Your Upcoming Ferry Trip Might Be Cancelled',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket_weather_warning',
            with: [
                'booking' => $this->booking,
                'schedule' => $this->schedule,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
