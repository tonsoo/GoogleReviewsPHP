<?php

class GoogleReviews{
    /*

    Declaração das constantes utilizadas para exibição das reviews completas ou simplificadas.

    */
    const FULL_REVIEW = 0;
    const SIMPLE_REVIEW = 1;

    private $apiKey;
    private $locationId;
    private $reviews;

    public function __construct(string $apiKey, string $locationId, int $reviewLevel=GoogleReviews::SIMPLE_REVIEW){
        $this->apiKey = $apiKey;
        $this->locationId = $locationId;

        $this->fetchReviews();
    }

    /*

    Função principal responsavel pela requisição ao endereço https://places.googleapis.com/v1/places/
    para recuperar as reviews do Google com base na chave da API e id do local

    */

    private function fetchReviews(int $reviewLevel=GoogleReviews::SIMPLE_REVIEW) : void{
        $query = http_build_query(['key' => $this->apiKey, 'fields' => 'reviews']);
        $apiUrl = "https://places.googleapis.com/v1/places/{$this->locationId}?{$query}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $chResult = curl_exec($ch);
        $data = json_decode($chResult, true);

        curl_close($ch);

        if(empty($data) || isset($data['error']) || !isset($data['reviews'])){
            return;
        }

        /*

        Modificação do retorno das reviews com base no tipo de reviews escolhida na criação do objeto

        */

        switch($reviewLevel){
            case GoogleReviews::SIMPLE_REVIEW:
                $reviews = [];
                
                foreach ($data['reviews'] as $review) {
                    $reviews[] = [
                        'author' => $review['authorAttribution']['displayName'],
                        'rating' => $review['rating'],
                        'translated_text' => $review['text']['text'],
                        'original_text' => $review['originalText']['text']
                    ];
                }
            break;

            default:
                $reviews = $data['reviews'];
            break;
        }

        $this->reviews = $reviews;
    }

    public function getReviews() : ?array{
        return $this->reviews;
    }

    public function iterate(callable $_) : void{
        if(empty($this->reviews)){
            return;
        }

        foreach($this->reviews as $review){
            call_user_func($_, $review);
        }
    }
}

// Criação do objeto passando os parametros obrigatorios 'apiKey' e 'locationId', junto do parametro facultativo 'reviewLevel'
$reviews = new GoogleReviews('id_da_api_do_google_aqui', 'id_do_local_do_google_aqui', GoogleReviews::SIMPLE_REVIEW);

// Recupera o array das reviews
$arrayDasReviews = $reviews->getReviews();

/*

Codigo com o 'arrayDasReviews'...

*/

// Renderização das reviews com uma arrow function
$reviews->iterate(fn($review) => print("<div>{$review['author']}</div>"));
