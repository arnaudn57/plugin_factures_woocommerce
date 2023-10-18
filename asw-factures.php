<?php
/*
Plugin Name: AN Factures
Description: Génération de factures pour WooCommerce
Version: 1.0
Author: Arnaud Nicastro
*/

// Inclure la bibliothèque TCPDF
require_once(plugin_dir_path(__FILE__) . 'tcpdf/tcpdf.php');

// Fonction pour générer un PDF à partir du modèle HTML
function generate_invoice_pdf($order_id)
{
    $order = wc_get_order($order_id);

    // Personnalisez le contenu HTML en fonction des données de la commande
    $order_data = $order->get_data();
    $customer_name = $order_data['billing']['first_name'] . ' ' . $order_data['billing']['last_name'];
    $order_date = $order->get_date_created()->format('Y-m-d');

    // Générez le HTML de la facture
    $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Facture</title>
            <style>
                .invoice-header {
        text-align: center;
        background-color: #333;
        color: #fff;
        padding: 10px;
    }

    .invoice-header h1 {
        font-size: 24px;
    }

    /* Styles pour les informations de la commande */
    .order-info {
        margin-bottom: 20px;
    }

    .order-info p {
        margin: 5px 0;
    }

    /* Styles pour les détails de la commande */
    .order-details table {
        width: 100%;
        border-collapse: collapse;
    }

    .order-details th, .order-details td {
        border: 1px solid #ddd;
        padding: 8px;
    }

    .order-details th {
        background-color: #f2f2f2;
    }
            </style>
        </head>
        <body>
            <div class="invoice-header">
                <h1>Facture</h1>
            </div>
            <div class="invoice-details">
                <div class="order-info">
                    <h2>Informations de la commande</h2>
                    <p><strong>Numéro de commande :</strong> ' . $order_id . '</p>
                    <p><strong>Date de commande :</strong> ' . $order_date . '</p>
                    <p><strong>Client :</strong> ' . $customer_name . '</p>
                </div>
                <div class="order-details">
                    <h2>Détails de la commande</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Quantité</th>
                                <th>Prix unitaire</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>';

    $items = $order->get_items();
    foreach ($items as $item) {
        $product_name = $item->get_name();
        $quantity = $item->get_quantity();
        $price = wc_price($item->get_total() / $quantity);
        $line_total = wc_price($item->get_total());

        $html .= '
                        <tr>
                            <td>' . $product_name . '</td>
                            <td>' . $quantity . '</td>
                            <td>' . $price . '</td>
                            <td>' . $line_total . '</td>
                        </tr>';
    }

    $html .= '
                        </tbody>
                    </table>
                </div>
            </div>
        </body>
        </html>
        ';

    // Créez une instance de TCPDF
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->writeHTML($html, true, false, true, false, '');

    $pdf->Output('invoice_' . $order_id . '.pdf', 'D');
}

// Ajoutez une colonne personnalisée "Télécharger Facture" à la liste des commandes
function add_invoice_download_column($columns)
{
    $new_columns = $columns;
    $new_columns['download_invoice'] = 'Télécharger Facture';
    $new_columns['print_invoice_3x'] = 'Imprimer en 3x';
    return $new_columns;
}
add_filter('manage_edit-shop_order_columns', 'add_invoice_download_column');

// Remplissez la colonne personnalisée avec un bouton de téléchargement de facture
function display_invoice_download_button($column)
{
    if ($column === 'download_invoice') {
        global $post;
        $order = wc_get_order($post->ID);
        $order_id = $order->get_id();
        echo '<a class="button" href="' . admin_url('admin-ajax.php?action=generate_invoice&order_id=' . $order_id) . '">Télécharger</a>';
    }

    if ($column === 'print_invoice_3x') {
        global $post;
        $order = wc_get_order($post->ID);
        $order_id = $order->get_id();
        echo '<a class="button" href="' . admin_url('admin-ajax.php?action=print_invoice_3x&order_id=' . $order_id) . '">Imprimer en 3x</a>';
    }
}

add_action('manage_shop_order_posts_custom_column', 'display_invoice_download_button');

// Créez une action pour générer un fichier PDF avec trois pages, chaque page contenant une facture
function print_invoice_3x_callback()
{
    if (isset($_GET['action']) && $_GET['action'] === 'print_invoice_3x' && isset($_GET['order_id'])) {
        $order_id = intval($_GET['order_id']);
        $order = wc_get_order($order_id);

        // Créez une instance de TCPDF
        $pdf = new TCPDF();

        for ($i = 0; $i < 3; $i++) {
            // Générez le contenu HTML de la facture
            $html = generate_invoice_html($order);

            // Ajoutez une nouvelle page au PDF
            $pdf->AddPage();
            $pdf->writeHTML($html, true, false, true, false, '');
        }

        // Proposez le téléchargement du PDF
        $pdf->Output('invoice_3x_' . $order_id . '.pdf', 'D');
    }
}
add_action('wp_ajax_print_invoice_3x', 'print_invoice_3x_callback');
add_action('wp_ajax_nopriv_print_invoice_3x', 'print_invoice_3x_callback');


// Fonction pour générer le HTML de la facture
function generate_invoice_html($order)
{
    // Générez le contenu HTML de la facture
    $order_data = $order->get_data();
    $customer_name = $order_data['billing']['first_name'] . ' ' . $order_data['billing']['last_name'];
    $order_date = $order->get_date_created()->format('Y-m-d');

    $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Facture</title>
            <style>
                /* Votre CSS personnalisé ici */
            </style>
        </head>
        <body>
            <div class="invoice-header">
                <h1>Facture</h1>
            </div>
            <div class="invoice-details">
                <div class="order-info">
                    <h2>Informations de la commande</h2>
                    <p><strong>Numéro de commande :</strong> ' . $order->get_id() . '</p>
                    <p><strong>Date de commande :</strong> ' . $order_date . '</p>
                    <p><strong>Client :</strong> ' . $customer_name . '</p>
                </div>
                <div class="order-details">
                    <h2>Détails de la commande</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Quantité</th>
                                <th>Prix unitaire</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>';

    $items = $order->get_items();
    foreach ($items as $item) {
        $product_name = $item->get_name();
        $quantity = $item->get_quantity();
        $price = wc_price($item->get_total() / $quantity);
        $line_total = wc_price($item->get_total());

        $html .= '
                            <tr>
                                <td>' . $product_name . '</td>
                                <td>' . $quantity . '</td>
                                <td>' . $price . '</td>
                                <td>' . $line_total . '</td>
                            </tr>';
    }

    $html .= '
                        </tbody>
                    </table>
                </div>
            </div>
        </body>
        </html>
        ';

    return $html;
}
