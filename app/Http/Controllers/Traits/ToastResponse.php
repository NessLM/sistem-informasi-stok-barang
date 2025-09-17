<?php

namespace App\Http\Controllers\Traits;

trait ToastResponse
{
    protected function toastSuccess(string $message, string $title = 'Berhasil')
    {
        return redirect()->back()
            ->with('toast', [
                'type'    => 'success',
                'title'   => $title,
                'message' => $message,
            ])
            ->with('no_loader', true);
    }

    protected function toastError(string $message, string $title = 'Gagal')
    {
        return redirect()->back()
            ->with('toast', [
                'type'    => 'danger',
                'title'   => $title,
                'message' => $message,
            ])
            ->with('no_loader', true);
    }
}
