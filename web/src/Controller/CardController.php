<?php

namespace Cards\Controller;

use Cards\Card;
use Cards\Barcode;
use Cards\Twig;

class CardController
{
    private string $appUrl;
    private array $user;

    public function __construct(string $appUrl, array $user)
    {
        $this->appUrl = $appUrl;
        $this->user = $user;
    }

    public function index(): void
    {
        $sort = $_GET['sort'] ?? 'smart';
        $cards = Card::all($this->user['id'], [
            'search' => $_GET['search'] ?? '',
            'sort' => $sort,
            'latitude' => null,
            'longitude' => null,
        ]);

        // Attach top locations and ensure usage_count for offline smart sort
        foreach ($cards as &$card) {
            $locs = Card::getTopLocations($card['id']);
            $card['locations'] = json_encode($locs);
            if (!isset($card['usage_count'])) {
                $card['usage_count'] = 0;
            }
        }
        unset($card);

        echo Twig::render('home.html.twig', [
            'app_url' => $this->appUrl,
            'user' => $this->user,
            'cards' => $cards,
            'search' => $_GET['search'] ?? '',
            'sort' => $sort,
        ]);
    }

    public function create(): void
    {
        echo Twig::render('card_form.html.twig', [
            'app_url' => $this->appUrl,
            'user' => $this->user,
        ]);
    }

    public function store(): void
    {
        Card::create($this->user['id'], $_POST);
        header('Location: /');
    }

    public function edit(string $id): void
    {
        $card = Card::find((int)$id, $this->user['id']);
        if (!$card) {
            header('Location: /');
            return;
        }
        echo Twig::render('card_form.html.twig', [
            'app_url' => $this->appUrl,
            'user' => $this->user,
            'card' => $card,
        ]);
    }

    public function update(string $id): void
    {
        Card::update((int)$id, $this->user['id'], $_POST);
        header('Location: /');
    }

    public function delete(string $id): void
    {
        Card::delete((int)$id, $this->user['id']);
        header('Location: /');
    }


    public function show(string $id): void
    {
        $card = Card::find((int)$id, $this->user['id']);
        if (!$card) {
            header('Location: /');
            return;
        }
        $barcodeSvg = Barcode::render($card['number'], $card['barcode_type']);
        echo Twig::render('card_show.html.twig', [
            'app_url' => $this->appUrl,
            'user' => $this->user,
            'card' => $card,
            'barcode_svg' => $barcodeSvg,
        ]);
    }
}
