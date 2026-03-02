<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PortfolioCompletionReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user)
    {
    }

    public function build()
    {
        $profile = $this->user->profile;
        $portfolio = $this->user->portfolio;
        $userName = $profile->first_name ?? $this->user->name ?? 'User';
        
        // Determine portfolio completion status
        $hasPortfolio = $portfolio ? true : false;
        $portfolioStatus = $portfolio ? $portfolio->status : 'none';
        $isComplete = $hasPortfolio && !empty($portfolio->title) && $portfolio->status === 'published';
        
        // Get portfolio URL
        $portfolioUrl = route('user.portfolios.index');

        return $this->subject('Complete Your Portfolio - ' . config('app.name'))
            ->view('emails.portfolio.completion-reminder', [
                'user' => $this->user,
                'profile' => $profile,
                'portfolio' => $portfolio,
                'userName' => $userName,
                'hasPortfolio' => $hasPortfolio,
                'portfolioStatus' => $portfolioStatus,
                'isComplete' => $isComplete,
                'url' => $portfolioUrl,
                'logoUrl' => asset('logo/Su250.png'),
            ]);
    }
}

