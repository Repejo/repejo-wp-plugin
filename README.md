# Repejo WP Plugin

Gör att Repejo-checkouten kan länkas med givar-id **direkt i adressen** i stället för som frågeparameter, och renderar checkouten via shortcode eller block.

```
Före:  https://exempel.se/signera?rp_hrid=abc-123
Efter: https://exempel.se/signera/abc-123
```

Det fina formatet ser tryggare ut för givaren. Pluginet ser till att WordPress inte svarar **404** på den nya adressen och skickar `id`:t vidare till checkouten via den parameter Repejo-backend redan förstår (`rp_hrid` som standard).

---

## Installation (för kunden)

1. **Installera & aktivera** pluginet (Tillägg → Lägg till nytt → Ladda upp zip).
2. Gå till **Inställningar → Permalänkar** och klicka **Spara ändringar** en gång.
   *(Aktiveringen försöker göra detta automatiskt; det här steget är en garanti.)*
3. Gå till **Inställningar → Repejo** och fyll i **Embed-URL** till checkouten.
4. Öppna sidan med checkouten, bocka i **"Aktivera Repejo-länkformat för denna sida"** i panelen till höger, och lägg in blocket **"Repejo Checkout"** (eller shortcode `[repejo_checkout]`) i sidans innehåll.
5. Testa `https://din-sajt.se/<sidan>/test-123` — samma sida ska visas, och adressen ska **inte** ändras tillbaka.

Pluginet visar tydliga varningar i wp-admin om något av ovanstående saknas
(permalänkar på "Enkel", embed-URL tom, eller en aktiverad sida som har
undersidor som kan skuggas).

## Hur det fungerar

| Del | Ansvar |
| --- | --- |
| `Rewrite` | En rewrite-regel per **markerad** sida: `^<sökväg>/(<id>)/?$` → sidan + `repejo_id`. Self-healing flush när regeluppsättningen ändras (även vid import/WP‑CLI). Aldrig flush på varje request. |
| `PageSettings` | Per-sida-kryssrutan i sidredigeraren. |
| `Canonical` | Hindrar WordPress kanoniska redirect från att kapa bort `id`:t — endast när vår query-var matchat. |
| `Embed` | `[repejo_checkout]`-shortcode + server-renderat block. iframe mot konfigurerad embed-URL, eller helt egen markup via filtret `repejo_wp_plugin_embed_html`. |
| `Diagnostics` | Synliga wp-admin-notiser för de tre klassiska tysta felen. |
| `Settings` | Embed-URL, parameternamn (`rp_hrid`), tillåtna tecken i id (regex), iframe-höjd. |
| `Updater` | Ett-klicks-uppdatering i wp-admin via GitHub Releases. |

### Egen embed-markup

Script-baserad embed i stället för iframe:

```php
add_filter( 'repejo_wp_plugin_embed_html', function ( $html, $id, $base ) {
    return '<div id="repejo" data-rp-hrid="' . esc_attr( $id ) . '"></div>'
         . '<script src="https://checkout.repejo.se/embed.js" async></script>';
}, 10, 3 );
```

## Utveckling

- PHP ≥ 7.4, WordPress ≥ 6.0. Inget byggsteg — blocket är build-free.
- En vanlig git-checkout fungerar; uppdateringskontrollen (PUC) är avstängd
  tills `vendor/` finns (`composer install`).

## Release

`Updater` använder [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) mot detta repos **GitHub Releases**.

```sh
# Höj versionen i repejo-wp-plugin.php (Version-headern) och commita.
git tag v0.2.0
git push origin v0.2.0
```

`.github/workflows/release.yml` bygger då en zip **med `vendor/`** och bifogar
den som release-asset. Kundernas WordPress upptäcker releasen och erbjuder
ett-klicks-uppdatering.

## Licens

GPL-2.0-or-later. Se [LICENSE](LICENSE).
