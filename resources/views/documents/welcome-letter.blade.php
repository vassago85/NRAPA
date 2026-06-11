{{--
    Legacy template path. The canonical welcome letter lives at
    `documents.letters.welcome` and uses the shared NRAPA official layout
    so it visually matches the rest of the certificates. Both renderers
    (PdfDocumentRenderer + FakeDocumentRenderer) map this slug to the
    canonical template, but we keep this thin pass-through so any caller
    or preview tool that resolves the literal stored slug still renders
    the same thing.
--}}
@include('documents.letters.welcome')
