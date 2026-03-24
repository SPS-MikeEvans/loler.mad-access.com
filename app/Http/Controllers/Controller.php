<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Support\ActionConfirmation;
use App\Support\ConfirmedAction;

abstract class Controller extends BaseController
{
    use AuthorizesRequests;

    protected function issueConfirmedAction(string $actionKey, string $subjectType, int $subjectId, string $phrase): ConfirmedAction
    {
        $confirmation = $this->makeConfirmedAction($actionKey, $subjectType, $subjectId, $phrase);

        app(ActionConfirmation::class)->issue($actionKey, $subjectType, $subjectId, $phrase);

        return $confirmation;
    }

    protected function makeConfirmedAction(string $actionKey, string $subjectType, int $subjectId, string $phrase): ConfirmedAction
    {
        return new ConfirmedAction(
            actionKey: $actionKey,
            subjectType: $subjectType,
            subjectId: $subjectId,
            phrase: $phrase,
            modalName: app(ActionConfirmation::class)->modalName($actionKey, $subjectId),
        );
    }

    protected function confirmedActionFailure(
        Request $request,
        ConfirmedAction $confirmation,
        string $message = 'The confirmation phrase was invalid or expired. Please try again.'
    ): RedirectResponse {
        $validator = Validator::make(
            $request->all(),
            ['confirmation_phrase' => ['required', 'string']]
        );

        $validator->errors()->add('confirmation_phrase', $message);

        return back()
            ->withErrors($validator)
            ->withInput()
            ->with('open_confirmation_modal', $confirmation->modalName);
    }

    protected function ensureConfirmedAction(Request $request, ConfirmedAction $confirmation): ?RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'confirmation_phrase' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('open_confirmation_modal', $confirmation->modalName);
        }

        $phrase = (string) $request->input('confirmation_phrase');
        $service = app(ActionConfirmation::class);

        if (! $service->matches($confirmation->actionKey, $confirmation->subjectType, $confirmation->subjectId, $phrase)) {
            return $this->confirmedActionFailure($request, $confirmation);
        }

        $service->consume($confirmation->actionKey, $confirmation->subjectType, $confirmation->subjectId);

        return null;
    }
}
