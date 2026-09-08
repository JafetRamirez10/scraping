<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Prospecting\CancelProspectSequenceAction;
use App\Enums\ProspectStatus;
use App\Enums\SequenceCancelReason;
use App\Enums\SuppressionReason;
use App\Models\Prospect;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnsubscribeController extends Controller
{
    public function __construct(
        private readonly CancelProspectSequenceAction $cancelSequence,
    ) {}

    public function __invoke(Request $request, Prospect $prospect): View
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $this->cancelSequence->addToSuppressionList($prospect->email, SuppressionReason::Unsubscribe);
        $this->cancelSequence->execute(
            $prospect,
            SequenceCancelReason::Unsubscribe,
            ProspectStatus::Unsubscribed,
        );

        $prospect->update(['unsubscribed_at' => now()]);

        return view('unsubscribe', [
            'email' => $prospect->email,
        ]);
    }
}
