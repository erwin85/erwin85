// <source lang=javascript>
/**
 * Localisatie van [[:Commons:Help:Gadget-HotCat]] voor de Nederlandse Wikipedia.
 */

if (typeof (HotCat) != 'undefined') {
    // HotCat is geladen.
    
    // Vertalingen van interface.
    HotCat.messages.cat_removed         = '[[:Categorie:$1]] verwijderd';
    HotCat.messages.template_removed    = '{{[[:Categorie:$1]]}} verwijderd';
    HotCat.messages.cat_added           = '[[:Categorie:$1]] toegevoegd';
    HotCat.messages.cat_keychange       = 'Sortering van [[:Categorie:$1]] aangepast naar ';
    HotCat.messages.cat_notFound        = 'Categorie $1 niet gevonden';
    HotCat.messages.cat_exists          = 'Categorie $1 bestaat al; niet toegevoegd.';
    HotCat.messages.cat_resolved        = ' (doorverwijzing naar [[:Categorie:$1]] gecorrigeerd)';
    HotCat.messages.uncat_removed       = '{{nocat}} verwijderd';
    HotCat.messages.using               = ' ([[Wikipedia:HotCat|HotCat.js]])';
    HotCat.messages.multi_change        = '$1 categorieën';
    HotCat.messages.commit              = 'Opslaan';
    HotCat.messages.ok                  = 'Opslaan';
    HotCat.messages.cancel              = 'Annuleren';
    HotCat.messages.multi_error         = 'Het is niet gelukt om de tekst op te halen. De wijzigingen zijn niet opgeslagen.';
 
    // Projectspecifieke variabelen
    HotCat.category_regexp              = '[Cc][Aa][Tt][Ee][Gg][Oo][Rr][Yy]|[Cc][Aa][Tt][Ee][Gg][Oo][Rr][Ii][Ee]';
    HotCat.category_canonical           = 'Categorie';
    HotCat.categories                   = 'Categorieën';
    HotCat.disambig_category            = 'Wikipedia:Doorverwijspagina';
    HotCat.redir_category               = null;
    HotCat.uncat_regexp                 = /\{\{\s*[Nn]ocat[^}]*\}\}\n/gm;
    HotCat.template_regexp              = '[Tt][Ee][Mm][Pp][Ll][Aa][Tt][Ee]|[Ss][Jj][Aa][Bb][Ll][Oo]{2}[Nn]';
    HotCat.template_categories          = { 
                                            // Verwijdersjablonen
                                            'Wikipedia:Nog niet gereed' : '[Ww]iu',
                                            'Wikipedia:Pagina weg'      : '[Ww]eg|[Aa]uteur',
                                            'Wikipedia:Auteur'          : '[Aa]uteur'
                                          };
    
    // Tooltips
    HotCat.tooltips.change              = 'Wijzigen';
    HotCat.tooltips.remove              = 'Verwijderen';
    HotCat.tooltips.add                 = 'Toevoegen';
    HotCat.tooltips.restore             = 'Herstellen';
    HotCat.tooltips.undo                = 'Herstellen';
    HotCat.tooltips.down                = 'Toon subcategorieën';
    HotCat.tooltips.up                  = 'Toon supercategorieën';
    HotCat.multi_tooltip                = 'Wijzig meerdere categorieën';
    
    // Zoekmachines
    HotCat.engine_names.searchindex     = 'Zoekindex';
    HotCat.engine_names.pagelist        = 'Categorielijst';
    HotCat.engine_names.combined        = 'Gecombineerd zoeken';
    HotCat.engine_names.subcat          = 'Subcategorieën';
    HotCat.engine_names.parentcat       = 'Supercategorieën';
    
    // Vul HotCat.template_categories aan met datumspecifieke categorieën.
    // Hulpvariabelen voor data.
    var fullMonthNames = new Array ('januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december');
    var currentYear = new Date().getFullYear();
    var previousYear = currentYear - 1;
    
    // Voeg {{Nocat}} toe.
    var templateRegex = '[Nn]ocat';
    for (i = 0; i <= fullMonthNames.length; i++) {
        HotCat.template_categories['Wikipedia:Nog te categoriseren sinds ' + fullMonthNames[i] + ' ' + currentYear] = templateRegex;
        HotCat.template_categories['Wikipedia:Nog te categoriseren sinds ' + fullMonthNames[i] + ' ' + previousYear] = templateRegex;
    }   
}
// </source>

